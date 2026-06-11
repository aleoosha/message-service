-- Принудительно создаем базу данных для аналитики, если её нет
CREATE DATABASE IF NOT EXISTS analytics;

-- 1. Создаем физическую аналитическую таблицу
CREATE TABLE IF NOT EXISTS analytics.notifications_report (
    message_id UUID,
    recipient String,
    status String,
    updated_at DateTime64(3)
) ENGINE = ReplacingMergeTree(updated_at)
PRIMARY KEY (message_id)
ORDER BY (message_id, recipient);

-- 2. Создаем виртуальную таблицу-потребитель Кафки
CREATE TABLE IF NOT EXISTS analytics.kafka_statuses_stream (
    message_id UUID,
    recipient String,
    status String,
    updated_at DateTime64(3)
) ENGINE = Kafka
SETTINGS 
    kafka_broker_list = 'kafka:9092',
    kafka_topic_list = 'message.statuses',
    kafka_group_name = 'clickhouse_analytics_group',
    kafka_format = 'JSONEachRow';

-- 3. Создаем Материализованное представление (мост)
CREATE MATERIALIZED VIEW IF NOT EXISTS analytics.mv_kafka_statuses TO analytics.notifications_report AS
SELECT message_id, recipient, status, updated_at
FROM analytics.kafka_statuses_stream;
