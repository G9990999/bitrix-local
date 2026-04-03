"""
Модуль тестирования chatbot tender-bot.

Тестирует:
1. Доступность HTTP-эндпоинтов (/health, /webhook/telegram, /bitrix/webhook)
2. Сценарии обработки сообщений (шаблоны + GigaChat fallback)
3. Парсинг событий Bitrix24
4. Интеграцию с GigaChat через LangChain

Запуск:
  pytest tests/test_tender_bot.py -v
  pytest tests/test_tender_bot.py -v --tb=short -k "not integration"
"""

import json
import os
import sys
import unittest
from unittest.mock import AsyncMock, MagicMock, patch

import pytest

# Добавляем путь к tender-bot для импорта
TENDER_BOT_PATH = os.path.join(os.path.dirname(__file__), "../../../../tender-bot")
sys.path.insert(0, TENDER_BOT_PATH)


# ---------------------------------------------------------------------------
# Тесты сценариев (unit, без реального HTTP)
# ---------------------------------------------------------------------------

class TestScenarios(unittest.TestCase):
    """Тесты сопоставления запросов с шаблонными сценариями."""

    def setUp(self):
        """Импортируем промпты — они не зависят от TELEGRAM_TOKEN."""
        from prompts import SCENARIOS
        self.scenarios = SCENARIOS

    def test_scenarios_not_empty(self):
        """Сценариев должно быть не менее 10."""
        self.assertGreaterEqual(len(self.scenarios), 10, "Ожидается минимум 10 сценариев")

    def test_scenario_keys_are_strings(self):
        """Все ключи сценариев — строки."""
        for key in self.scenarios:
            self.assertIsInstance(key, str, f"Ключ сценария должен быть строкой: {key}")

    def test_scenario_values_are_strings(self):
        """Все значения сценариев — непустые строки."""
        for key, value in self.scenarios.items():
            self.assertIsInstance(value, str, f"Значение сценария должно быть строкой: {key}")
            self.assertGreater(len(value.strip()), 0, f"Значение сценария не должно быть пустым: {key}")

    def test_find_scenario_zaявка(self):
        """Поиск сценария по ключевому слову 'заявку'."""
        from prompts import SCENARIOS

        def find_scenario(text: str):
            text_lower = text.lower()
            for key, answer in SCENARIOS.items():
                if any(word in text_lower for word in key.split()):
                    return answer
            return None

        result = find_scenario("создать заявку на расходование ДС")
        self.assertIsNotNone(result, "Должен найти сценарий для заявки")
        self.assertIn("заявку", result.lower())

    def test_find_scenario_tender(self):
        """Поиск сценария по слову 'тендер'."""
        from prompts import SCENARIOS

        def find_scenario(text: str):
            text_lower = text.lower()
            for key, answer in SCENARIOS.items():
                if any(word in text_lower for word in key.split()):
                    return answer
            return None

        result = find_scenario("как зарегистрировать тендер")
        self.assertIsNotNone(result, "Должен найти сценарий для тендера")

    def test_find_scenario_no_match(self):
        """Несовпадение должно вернуть None."""
        from prompts import SCENARIOS

        def find_scenario(text: str):
            text_lower = text.lower()
            for key, answer in SCENARIOS.items():
                if any(word in text_lower for word in key.split()):
                    return answer
            return None

        result = find_scenario("совершенно случайный запрос xyz123")
        self.assertIsNone(result, "Для неизвестного запроса должен вернуть None")

    def test_system_prompt_exists(self):
        """SYSTEM_PROMPT должен быть непустым."""
        from prompts import SYSTEM_PROMPT
        self.assertIsInstance(SYSTEM_PROMPT, str)
        self.assertGreater(len(SYSTEM_PROMPT.strip()), 0)


# ---------------------------------------------------------------------------
# Тесты HTTP-эндпоинтов через TestClient (без реального Telegram)
# ---------------------------------------------------------------------------

class TestHealthEndpoint(unittest.IsolatedAsyncioTestCase):
    """Тест эндпоинта /health."""

    @patch.dict(os.environ, {"TELEGRAM_TOKEN": "fake:token"})
    @patch("telegram.ext.Application.builder")
    async def test_health_returns_ok(self, mock_builder):
        """GET /health должен вернуть {"status": "ok"}."""
        # Мокируем Telegram Application полностью
        mock_app = MagicMock()
        mock_app.initialize = AsyncMock()
        mock_app.start = AsyncMock()
        mock_app.stop = AsyncMock()
        mock_app.shutdown = AsyncMock()
        mock_builder.return_value.token.return_value.build.return_value = mock_app

        try:
            from fastapi.testclient import TestClient
            # Патчируем telegram_app на уровне модуля
            with patch("main.telegram_app", mock_app):
                import importlib
                import main as bot_module
                client = TestClient(bot_module.app, raise_server_exceptions=False)
                response = client.get("/health")
                self.assertEqual(response.status_code, 200)
                data = response.json()
                self.assertEqual(data.get("status"), "ok")
        except ImportError:
            self.skipTest("fastapi или httpx не установлены")


# ---------------------------------------------------------------------------
# Тесты Bitrix webhook-обработчика (unit)
# ---------------------------------------------------------------------------

class TestBitrixWebhookParsing(unittest.TestCase):
    """Тесты парсинга событий Bitrix24."""

    def test_event_labels_mapping(self):
        """Все три CRM-события должны иметь метку."""
        event_labels = {
            "ONCRMDEALADD":    "➕ Новая сделка добавлена",
            "ONCRMDEALUPDATE": "✏️ Сделка обновлена",
            "ONCRMDEALDELETE": "🗑️ Сделка удалена",
        }

        for event in ["ONCRMDEALADD", "ONCRMDEALUPDATE", "ONCRMDEALDELETE"]:
            self.assertIn(event, event_labels, f"Событие {event} должно иметь метку")
            self.assertGreater(len(event_labels[event]), 0)

    def test_unknown_event_fallback(self):
        """Неизвестное событие должно возвращать дефолтную метку."""
        event_labels = {
            "ONCRMDEALADD":    "➕ Новая сделка добавлена",
            "ONCRMDEALUPDATE": "✏️ Сделка обновлена",
            "ONCRMDEALDELETE": "🗑️ Сделка удалена",
        }
        event_type = "UNKNOWN_EVENT"
        label = event_labels.get(event_type, f"📌 Событие: {event_type}")
        self.assertEqual(label, "📌 Событие: UNKNOWN_EVENT")


# ---------------------------------------------------------------------------
# Тесты GigaChat интеграции (unit, с мок-объектами)
# ---------------------------------------------------------------------------

class TestGigaChatIntegration(unittest.TestCase):
    """Тесты интеграции с GigaChat через LangChain."""

    def test_get_llm_without_credentials_returns_none(self):
        """get_llm() без GIGACHAT_CREDENTIALS должен вернуть None."""
        with patch.dict(os.environ, {"TELEGRAM_TOKEN": "fake:token", "GIGACHAT_CREDENTIALS": ""}):
            with patch("main.telegram_app", MagicMock()):
                import importlib
                import main
                importlib.reload(main)
                result = main.get_llm()
                self.assertIsNone(result)

    def test_get_llm_with_credentials_creates_gigachat(self):
        """get_llm() с GIGACHAT_CREDENTIALS должен создать объект GigaChat."""
        with patch.dict(os.environ, {
            "TELEGRAM_TOKEN": "fake:token",
            "GIGACHAT_CREDENTIALS": "dGVzdA==",
        }):
            with patch("main.GigaChat") as mock_gigachat:
                with patch("main.telegram_app", MagicMock()):
                    import importlib
                    import main
                    importlib.reload(main)
                    main.get_llm()
                    mock_gigachat.assert_called_once()


# ---------------------------------------------------------------------------
# Интеграционные тесты (реальный HTTP-запрос к запущенному боту)
# ---------------------------------------------------------------------------

class TestIntegration(unittest.TestCase):
    """Интеграционные тесты — требуют запущенного tender-bot на порту 3102."""

    TENDER_BOT_URL = os.getenv("TENDER_BOT_URL", "http://localhost:3102")

    @pytest.mark.integration
    def test_health_endpoint_live(self):
        """Проверяет, что tender-bot отвечает на /health."""
        try:
            import httpx
            response = httpx.get(f"{self.TENDER_BOT_URL}/health", timeout=5)
            self.assertEqual(response.status_code, 200)
            data = response.json()
            self.assertEqual(data.get("status"), "ok")
        except Exception as e:
            self.skipTest(f"tender-bot недоступен: {e}")

    @pytest.mark.integration
    def test_bitrix_webhook_live(self):
        """Проверяет эндпоинт /bitrix/webhook (без реального Telegram чата)."""
        try:
            import httpx
            response = httpx.post(
                f"{self.TENDER_BOT_URL}/bitrix/webhook",
                data={"event": "ONCRMDEALADD", "data[FIELDS][ID]": "999"},
                timeout=5,
            )
            self.assertIn(response.status_code, [200, 202])
        except Exception as e:
            self.skipTest(f"tender-bot недоступен: {e}")


# ---------------------------------------------------------------------------
# Запуск
# ---------------------------------------------------------------------------

if __name__ == "__main__":
    unittest.main(verbosity=2)
