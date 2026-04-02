"""
Telegram-бот тендерного отдела — LangChain + GigaChat
FastAPI-приложение: принимает вебхуки от Telegram и Bitrix24.
"""

import asyncio
import logging
import os
from contextlib import asynccontextmanager

import httpx
from fastapi import FastAPI, Request, Response
from telegram import Update, Bot
from telegram.ext import Application, CommandHandler, MessageHandler, filters, ContextTypes
from langchain_community.chat_models import GigaChat
from langchain.schema import HumanMessage, SystemMessage

from prompts import SYSTEM_PROMPT, SCENARIOS

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

TELEGRAM_TOKEN = os.environ["TELEGRAM_TOKEN"]
GIGACHAT_CREDENTIALS = os.getenv("GIGACHAT_CREDENTIALS", "")
GIGACHAT_SCOPE = os.getenv("GIGACHAT_SCOPE", "GIGACHAT_API_PERS")

# Инициализация GigaChat через LangChain
def get_llm():
    if not GIGACHAT_CREDENTIALS:
        logger.warning("GIGACHAT_CREDENTIALS не задан — бот будет отвечать по шаблонам")
        return None
    return GigaChat(
        credentials=GIGACHAT_CREDENTIALS,
        scope=GIGACHAT_SCOPE,
        verify_ssl_certs=False,
        streaming=False,
    )


def find_scenario(text: str) -> str | None:
    """Ищет заготовленный сценарий по ключевым словам в тексте запроса."""
    text_lower = text.lower()
    for key, answer in SCENARIOS.items():
        if any(word in text_lower for word in key.split()):
            return answer
    return None


async def answer_with_llm(llm, user_text: str) -> str:
    """Генерирует ответ через GigaChat."""
    messages = [
        SystemMessage(content=SYSTEM_PROMPT),
        HumanMessage(content=user_text),
    ]
    try:
        response = await asyncio.to_thread(llm.invoke, messages)
        return response.content
    except Exception as e:
        logger.error("GigaChat error: %s", e)
        return "Извините, сервис временно недоступен. Попробуйте позже или обратитесь к администратору."


async def handle_message(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    """Обработчик текстовых сообщений."""
    user_text = update.message.text
    logger.info("Получен запрос: %s", user_text)

    # Сначала ищем по шаблонам — быстро и без API-вызовов
    scenario_answer = find_scenario(user_text)
    if scenario_answer:
        await update.message.reply_text(scenario_answer, parse_mode="Markdown")
        return

    # Если шаблон не найден — обращаемся к GigaChat
    llm = get_llm()
    if llm:
        answer = await answer_with_llm(llm, user_text)
    else:
        answer = (
            "Я не нашёл готового ответа на ваш вопрос.\n"
            "Пожалуйста, уточните запрос или обратитесь к администратору.\n\n"
            "Подсказки по запросам:\n"
            "• создать заявку на расходование ДС\n"
            "• как зарегистрировать тендер\n"
            "• как поставить задачу\n"
            "• как проверить дедлайн тендера\n"
            "• как посмотреть отчёт по тендерам"
        )

    await update.message.reply_text(answer, parse_mode="Markdown")


async def handle_start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    await update.message.reply_text(
        "👋 Привет! Я бот техподдержки тендерного отдела.\n\n"
        "Задайте вопрос, например:\n"
        "• *создать заявку на расходование ДС*\n"
        "• *как зарегистрировать тендер*\n"
        "• *как поставить задачу*\n"
        "• *как проверить дедлайн тендера*",
        parse_mode="Markdown",
    )


# Инициализация Telegram Application
telegram_app = Application.builder().token(TELEGRAM_TOKEN).build()
telegram_app.add_handler(CommandHandler("start", handle_start))
telegram_app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, handle_message))


@asynccontextmanager
async def lifespan(app: FastAPI):
    await telegram_app.initialize()
    await telegram_app.start()
    yield
    await telegram_app.stop()
    await telegram_app.shutdown()


app = FastAPI(lifespan=lifespan)


@app.get("/health")
async def health():
    return {"status": "ok"}


@app.post("/webhook/telegram")
async def telegram_webhook(request: Request):
    """Принимает обновления от Telegram."""
    data = await request.json()
    update = Update.de_json(data, telegram_app.bot)
    await telegram_app.process_update(update)
    return Response(status_code=200)


@app.post("/bitrix/webhook")
async def bitrix_webhook(request: Request):
    """Принимает события из Bitrix24 и уведомляет в Telegram."""
    data = await request.form()
    event_type = data.get("event", "")
    deal_id    = data.get("data[FIELDS][ID]", "")

    logger.info("Bitrix event: %s, deal_id: %s", event_type, deal_id)

    # Маппинг событий на Telegram-чаты (настраивается через env)
    chat_id = os.getenv("TELEGRAM_CHAT_ID", "")
    if not chat_id:
        return {"status": "ok", "note": "TELEGRAM_CHAT_ID не настроен"}

    event_labels = {
        "ONCRMDEALADD":    "➕ Новая сделка добавлена",
        "ONCRMDEALUPDATE": "✏️ Сделка обновлена",
        "ONCRMDEALDELETE": "🗑️ Сделка удалена",
    }
    label = event_labels.get(event_type, f"📌 Событие: {event_type}")
    message = f"{label}\nID сделки: {deal_id}"

    bot = telegram_app.bot
    await bot.send_message(chat_id=chat_id, text=message)

    return {"status": "ok"}
    
