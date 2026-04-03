# chatbot.test — Модуль тестирования tender-bot

Набор тестов для проверки работоспособности `tender-bot` на базе LangChain + GigaChat.

## Запуск тестов

```bash
# Unit-тесты (без запущенного бота)
cd server-ansible/home/upload/local/modules/chatbot.test
pip install -r requirements.txt
pytest tests/test_tender_bot.py -v -k "not integration"

# Интеграционные тесты (требуют запущенного tender-bot)
docker compose up tender-bot -d
pytest tests/test_tender_bot.py -v -m integration
```

## Что тестируется

| Класс | Описание |
|-------|----------|
| `TestScenarios` | Совпадение запросов со сценариями, SYSTEM_PROMPT |
| `TestHealthEndpoint` | GET /health возвращает `{"status": "ok"}` |
| `TestBitrixWebhookParsing` | Маппинг событий CRM на метки |
| `TestGigaChatIntegration` | get_llm() с/без GIGACHAT_CREDENTIALS |
| `TestIntegration` | Живые HTTP-запросы к запущенному боту |
