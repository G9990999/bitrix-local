"""
FastAPI microservice exposing the role prediction model.
Serves as the bridge between the Bitrix24 PHP module and the Python ML model.

Usage:
    pip install fastapi uvicorn
    uvicorn api:app --host 127.0.0.1 --port 8765

Endpoints:
    POST /predict   { "position": "Менеджер по продажам" }
    GET  /roles     -> list of all available roles
    GET  /health    -> health check
"""

import csv
from pathlib import Path
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

import model as role_model

app = FastAPI(
    title="Role Access Prediction API",
    description="Predicts 1C Bitrix24 access roles for a given job title",
    version="1.0.0",
)

DATA_DIR = Path(__file__).parent / "data"
ROLES_CSV = DATA_DIR / "roles-list.csv"


class PredictRequest(BaseModel):
    position: str


class PredictResponse(BaseModel):
    position: str
    permissions: dict[str, bool]
    csv: str


class RolesResponse(BaseModel):
    roles: list[str]


def _to_csv(permissions: dict[str, bool]) -> str:
    lines = ["role,enabled"]
    for role, enabled in permissions.items():
        lines.append(f'"{role}",{"true" if enabled else "false"}')
    return "\n".join(lines)


def _load_roles() -> list[str]:
    roles = []
    with open(ROLES_CSV, encoding="utf-8") as f:
        reader = csv.DictReader(f)
        for row in reader:
            roles.append(row["name"])
    return roles


@app.get("/health")
def health():
    return {"status": "ok"}


@app.get("/roles", response_model=RolesResponse)
def get_roles():
    return {"roles": _load_roles()}


@app.post("/predict", response_model=PredictResponse)
def predict(req: PredictRequest):
    if not req.position.strip():
        raise HTTPException(status_code=400, detail="position must not be empty")
    try:
        permissions = role_model.predict(req.position)
    except FileNotFoundError:
        raise HTTPException(
            status_code=503,
            detail="Model not trained yet. Run: python model.py train"
        )
    return {
        "position": req.position,
        "permissions": permissions,
        "csv": _to_csv(permissions),
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8765)
