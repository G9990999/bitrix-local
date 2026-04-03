"""
Role Access Prediction Model (scikit-learn).

Trains a multi-label classifier on top of TF-IDF text features.
Given a text description of a position, predicts which roles (checkboxes) should be enabled.

Usage:
    python model.py train        # Train and save the model
    python model.py predict "Менеджер по продажам"   # Predict for a position
"""

import json
import csv
import sys
import pickle
import logging
from pathlib import Path

import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.multiclass import OneVsRestClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import MultiLabelBinarizer
from sklearn.metrics import classification_report

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

DATA_DIR = Path(__file__).parent / "data"
MODELS_DIR = Path(__file__).parent / "models"
TRAINING_JSON = DATA_DIR / "training_data.json"
ROLES_CSV = DATA_DIR / "roles-list.csv"
MODEL_PATH = MODELS_DIR / "role_model.pkl"
BINARIZER_PATH = MODELS_DIR / "binarizer.pkl"


def load_roles() -> list[str]:
    roles = []
    with open(ROLES_CSV, encoding="utf-8") as f:
        reader = csv.DictReader(f)
        for row in reader:
            roles.append(row["name"])
    return roles


def load_training_data() -> tuple[list[str], list[list[str]]]:
    """Load training data. Returns (texts, list_of_active_roles)."""
    with open(TRAINING_JSON, encoding="utf-8") as f:
        data = json.load(f)

    texts = []
    labels = []
    for item in data:
        texts.append(item["position"] + " " + item.get("profile", ""))
        active_roles = [role for role, enabled in item["permissions"].items() if enabled]
        labels.append(active_roles)
    return texts, labels


def train() -> None:
    MODELS_DIR.mkdir(parents=True, exist_ok=True)
    logger.info("Loading training data...")
    texts, labels = load_training_data()
    all_roles = load_roles()

    logger.info(f"Training on {len(texts)} samples with {len(all_roles)} possible roles")

    mlb = MultiLabelBinarizer(classes=all_roles)
    Y = mlb.fit_transform(labels)

    pipeline = Pipeline([
        ("tfidf", TfidfVectorizer(
            analyzer="char_wb",
            ngram_range=(2, 4),
            min_df=1,
            max_features=10000,
            sublinear_tf=True,
        )),
        ("clf", OneVsRestClassifier(
            LogisticRegression(
                C=5.0,
                max_iter=500,
                solver="lbfgs",
                class_weight="balanced",
            )
        )),
    ])

    pipeline.fit(texts, Y)

    with open(MODEL_PATH, "wb") as f:
        pickle.dump(pipeline, f)
    with open(BINARIZER_PATH, "wb") as f:
        pickle.dump(mlb, f)

    # Simple self-evaluation
    Y_pred = pipeline.predict(texts)
    report = classification_report(Y, Y_pred, target_names=all_roles, zero_division=0)
    logger.info("Training classification report:\n" + report)
    logger.info(f"Model saved to {MODEL_PATH}")


def load_model():
    with open(MODEL_PATH, "rb") as f:
        pipeline = pickle.load(f)
    with open(BINARIZER_PATH, "rb") as f:
        mlb = pickle.load(f)
    return pipeline, mlb


def predict(position: str) -> dict[str, bool]:
    """Return dict of {role_name: True/False} for the given position description."""
    pipeline, mlb = load_model()
    X = [position]
    Y_pred = pipeline.predict(X)
    active_roles = set(mlb.inverse_transform(Y_pred)[0])
    all_roles = load_roles()
    return {role: (role in active_roles) for role in all_roles}


def predict_proba(position: str) -> dict[str, float]:
    """Return dict of {role_name: probability} for the given position description."""
    pipeline, mlb = load_model()
    X = [position]
    proba = pipeline.predict_proba(X)[0]
    all_roles = mlb.classes_
    return {role: float(p) for role, p in zip(all_roles, proba)}


def predict_to_csv(position: str, output_path: str) -> None:
    """Predict and save result as CSV."""
    import csv as csv_mod
    permissions = predict(position)
    with open(output_path, "w", newline="", encoding="utf-8") as f:
        writer = csv_mod.writer(f)
        writer.writerow(["role", "enabled"])
        for role, enabled in permissions.items():
            writer.writerow([role, "true" if enabled else "false"])
    logger.info(f"Predictions saved to {output_path}")


def predict_to_json(position: str, output_path: str) -> None:
    """Predict and save result as JSON."""
    permissions = predict(position)
    result = {
        "position": position,
        "permissions": permissions,
    }
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    logger.info(f"Predictions saved to {output_path}")


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python model.py train | python model.py predict <position>")
        sys.exit(1)

    cmd = sys.argv[1]
    if cmd == "train":
        train()
    elif cmd == "predict":
        if len(sys.argv) < 3:
            print("Usage: python model.py predict <position>")
            sys.exit(1)
        position = " ".join(sys.argv[2:])
        result = predict(position)
        print(json.dumps(result, ensure_ascii=False, indent=2))
    else:
        print(f"Unknown command: {cmd}")
        sys.exit(1)
    