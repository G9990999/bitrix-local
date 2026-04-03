"""
Tests for the role access prediction model.
Run: python -m pytest tests/ -v
"""
import sys
import json
import csv
from pathlib import Path

import pytest

# Add parent dir to path
sys.path.insert(0, str(Path(__file__).parent.parent))

import model as role_model


@pytest.fixture(scope="module", autouse=True)
def trained_model(tmp_path_factory):
    """Ensure model is trained before tests."""
    # Model should already be trained in models/ dir; if not, train it.
    if not role_model.MODEL_PATH.exists():
        role_model.train()


def test_roles_csv_exists():
    assert role_model.ROLES_CSV.exists(), "roles-list.csv must exist"


def test_roles_csv_content():
    roles = role_model.load_roles()
    assert len(roles) == 24
    assert "Менеджер по продажам" in roles
    assert "Менеджер по закупкам" in roles
    assert "Администраторы" in roles
    assert "Только чтение" in roles


def test_predict_returns_all_roles():
    result = role_model.predict("Менеджер по продажам")
    roles = role_model.load_roles()
    assert set(result.keys()) == set(roles)


def test_predict_sales_manager():
    result = role_model.predict("Менеджер по продажам")
    assert result["Менеджер по продажам"] is True
    assert result["Только чтение"] is True
    assert result["Администраторы"] is False


def test_predict_accountant():
    result = role_model.predict("Бухгалтер")
    assert result["Бухгалтер"] is True
    assert result["Администраторы"] is False


def test_predict_administrator():
    result = role_model.predict("Администратор системы")
    assert result["Администраторы"] is True


def test_predict_warehouse():
    result = role_model.predict("Кладовщик")
    assert result["Работник склада"] is True


def test_predict_returns_booleans():
    result = role_model.predict("Менеджер по закупкам")
    for key, val in result.items():
        assert isinstance(val, bool), f"Expected bool for {key}, got {type(val)}"


def test_predict_proba_returns_floats():
    result = role_model.predict_proba("Менеджер по продажам")
    roles = role_model.load_roles()
    assert set(result.keys()) == set(roles)
    for key, val in result.items():
        assert isinstance(val, float)
        assert 0.0 <= val <= 1.0


def test_predict_to_csv(tmp_path):
    output = str(tmp_path / "result.csv")
    role_model.predict_to_csv("Менеджер по продажам", output)
    assert Path(output).exists()
    with open(output, encoding="utf-8") as f:
        reader = csv.DictReader(f)
        rows = list(reader)
    assert len(rows) == 24
    roles_in_csv = {r["role"] for r in rows}
    assert "Менеджер по продажам" in roles_in_csv


def test_predict_to_json(tmp_path):
    output = str(tmp_path / "result.json")
    role_model.predict_to_json("Менеджер по продажам", output)
    assert Path(output).exists()
    with open(output, encoding="utf-8") as f:
        data = json.load(f)
    assert data["position"] == "Менеджер по продажам"
    assert "permissions" in data
    assert len(data["permissions"]) == 24
