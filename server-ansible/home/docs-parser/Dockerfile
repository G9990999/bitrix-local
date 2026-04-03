FROM python:3.11-slim

WORKDIR /app

# Зависимости системы для lxml
RUN apt-get update && apt-get install -y --no-install-recommends \
    gcc libxml2-dev libxslt-dev \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY main.py .

# Однократный запуск или HTTP-сервис
CMD ["python", "main.py"]
