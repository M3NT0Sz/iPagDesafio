import pika
import os

rabbit_host = os.getenv('RABBITMQ_HOST', 'rabbitmq')
queue = 'order_status_updates'

connection = pika.BlockingConnection(pika.ConnectionParameters(host=rabbit_host))
channel = connection.channel()
channel.queue_declare(queue=queue, durable=True)

def callback(ch, method, properties, body):
    print(f"[PYTHON] Mensagem recebida: {body.decode()}")

channel.basic_consume(queue=queue, on_message_callback=callback, auto_ack=True)
print(f"[PYTHON] Aguardando mensagens na fila '{queue}'. Para sair pressione CTRL+C")
try:
    channel.start_consuming()
except KeyboardInterrupt:
    print("[PYTHON] Encerrando consumidor.")
    channel.stop_consuming()
connection.close()