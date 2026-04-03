"""
Generate training data using LangChain + GigaChat.
For each role in roles-list.csv, asks GigaChat which checkboxes (roles) should be enabled.
Saves results to data/training_data.json.

Usage:
    GIGACHAT_CREDENTIALS=<your_token> python train_data.py
"""

import os
import json
import csv
import logging
from pathlib import Path

from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import JsonOutputParser
from gigachat import GigaChat
from langchain_gigachat.chat_models import GigaChat as LangChainGigaChat

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

DATA_DIR = Path(__file__).parent / "data"
ROLES_CSV = DATA_DIR / "roles-list.csv"
OUTPUT_JSON = DATA_DIR / "training_data.json"


def load_roles() -> list[dict]:
    roles = []
    with open(ROLES_CSV, encoding="utf-8") as f:
        reader = csv.DictReader(f)
        for row in reader:
            roles.append(row)
    return roles


def build_prompt(all_roles: list[dict]) -> ChatPromptTemplate:
    roles_list_str = "\n".join(
        f"- {r['name']} (профиль: {r['profile']})" for r in all_roles
    )
    system_msg = (
        "Ты — эксперт по системе 1C:Управление торговлей / Bitrix24. "
        "Тебе дан список доступных ролей (групп доступа) в системе. "
        "По описанию должности пользователя определи, какие роли ему нужны. "
        "Верни ответ СТРОГО в формате JSON: объект, где ключ — название роли, значение — true (нужна) или false (не нужна). "
        "Включи ВСЕ роли из списка.\n\n"
        f"Список всех ролей:\n{roles_list_str}"
    )
    human_msg = "Должность пользователя: {position}"
    return ChatPromptTemplate.from_messages([
        ("system", system_msg),
        ("human", human_msg),
    ])


def generate_training_data(credentials: str) -> list[dict]:
    all_roles = load_roles()
    role_names = [r["name"] for r in all_roles]

    llm = LangChainGigaChat(
        credentials=credentials,
        verify_ssl_certs=False,
        model="GigaChat",
    )

    prompt = build_prompt(all_roles)
    parser = JsonOutputParser()
    chain = prompt | llm | parser

    training_data = []

    for role in all_roles:
        position = role["name"]
        logger.info(f"Generating permissions for: {position}")
        try:
            result = chain.invoke({"position": position})
            # Normalize: ensure all roles are present
            permissions = {}
            for rn in role_names:
                permissions[rn] = bool(result.get(rn, False))
            training_data.append({
                "position": position,
                "profile": role["profile"],
                "permissions": permissions,
            })
        except Exception as e:
            logger.error(f"Failed for {position}: {e}")
            # Fallback: only assign the matching role
            permissions = {rn: (rn == position or rn == role["profile"]) for rn in role_names}
            training_data.append({
                "position": position,
                "profile": role["profile"],
                "permissions": permissions,
            })

    return training_data


def main():
    credentials = os.environ.get("GIGACHAT_CREDENTIALS")
    if not credentials:
        raise EnvironmentError(
            "Set GIGACHAT_CREDENTIALS environment variable with your GigaChat API key."
        )

    data = generate_training_data(credentials)

    OUTPUT_JSON.parent.mkdir(parents=True, exist_ok=True)
    with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    logger.info(f"Saved {len(data)} training samples to {OUTPUT_JSON}")


if __name__ == "__main__":
    main()
