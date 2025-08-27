import pika
import os
import signal
import sys
import time

def health_check():
    print("[HEALTH] Worker está rodando e conectado ao RabbitMQ.")

def graceful_shutdown(signum, frame):
    print("[SHUTDOWN] Worker finalizando com segurança...")
    sys.exit(0)

if __name__ == "__main__":
    # Configuração por ambiente
    rabbit_host = os.getenv('RABBITMQ_HOST', 'rabbitmq')
    rabbit_queue = os.getenv('RABBITMQ_QUEUE', 'order_status_updates')
    rabbit_dlq = os.getenv('RABBITMQ_DLQ', 'order_status_dlq')

    # Graceful shutdown
    signal.signal(signal.SIGTERM, graceful_shutdown)
    signal.signal(signal.SIGINT, graceful_shutdown)

    # Conexão RabbitMQ
    connection = pika.BlockingConnection(pika.ConnectionParameters(host=rabbit_host))
    channel = connection.channel()
    channel.queue_declare(queue=rabbit_queue, durable=True)
    channel.queue_declare(queue=rabbit_dlq, durable=True)

    def callback(ch, method, properties, body):
        try:
            print(f"[INFO] Mensagem recebida: {body.decode()}")
            # Simula processamento e log
            time.sleep(1)
            print("[INFO] Processamento OK. Log registrado.")
            ch.basic_ack(delivery_tag=method.delivery_tag)
        except Exception as e:
            print(f"[ERROR] Falha ao processar mensagem: {e}")
            # Envia para DLQ
            ch.basic_publish(exchange='', routing_key=rabbit_dlq, body=body)
            ch.basic_ack(delivery_tag=method.delivery_tag)

    channel.basic_qos(prefetch_count=1)
    channel.basic_consume(queue=rabbit_queue, on_message_callback=callback)

    print('[WORKER] Aguardando mensagens. Pressione Ctrl+C para sair.')
    health_check()
    try:
        channel.start_consuming()
    except KeyboardInterrupt:
        graceful_shutdown(None, None)
