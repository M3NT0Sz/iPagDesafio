
# iPagDesafio

## Plano de Trabalho - Desenvolvimento da Aplicação

### Fase 1: Planejamento & Setup Inicial (1–2 dias)
- **Dia 1:**
	- ✔️ Análise do desafio
	- ✔️ Definição da stack técnica
	- ✔️ Configuração inicial com Docker
	- ✔️ Estrutura do repositório
- **Dia 2:**
	- ✔️ Modelagem do banco de dados
	- ✔️ Definição de endpoints da API
	- ✔️ Workflow do Worker
	- ✔️ Documentação inicial

### Fase 2: Implementação da API REST (Dias 3–4)
- **Dia 3:**
	- ✔️ Configuração da API com Slim
	- ✔️ Conexão MySQL
	- ✔️ Implementação de endpoints CRUD (POST /orders, GET /orders/{order_id}, PUT /orders/{order_id}/status, GET /orders, GET /orders/summary)
- **Dia 4:**
	- ✔️ Finalização de endpoints
	- ✔️ Integração com RabbitMQ (publicação na fila ao atualizar status)
	- Testes manuais (parcialmente realizados)

### Fase 3: Implementação do Worker (Dias 5–6)
**Dia 5:**
	- ✔️ Desenvolvimento do consumer RabbitMQ (PHP implementado, bug documentado; Python funcional e recomendado)
	- ✔️ Integração com Docker Compose
	- ✔️ Testes de fluxo completo (com worker Python)
**Dia 6:**
	- ✔️ Refinamento
	- ✔️ Tratamento de erros
	- ✔️ Testes adicionais (incluindo testes automatizados com PHPUnit)

# iPagDesafio

API RESTful para gerenciamento de pedidos, integração com RabbitMQ e processamento assíncrono via worker Python. Projeto desenvolvido para o desafio iPag, com Docker Compose, testes automatizados, documentação Swagger e diferenciais avançados.

---

## Como Executar

1. **Clone o repositório e suba o ambiente:**
   ```sh
   docker-compose up --build
   ```
2. **Acesse a API:** http://localhost:8080
3. **Acesse o RabbitMQ:** http://localhost:15672 (user: guest, pass: guest)
4. **Execute o worker Python:**
   ```sh
   docker-compose exec app python public/worker_health.py
   ```
5. **Rode os testes:**
   ```sh
   composer install
   php vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/Api/OrdersApiTest.php
   ```

---

## Checklist de Entrega

- [x] API REST (Slim)
- [x] Banco MySQL (migrations)
- [x] RabbitMQ + Worker Python
- [x] Endpoints REST completos
- [x] Testes automatizados (PHPUnit)
- [x] Docker Compose funcional
- [x] Documentação Swagger/OpenAPI
- [x] Health check, rate limiting, DLQ, pooling, métricas, collection Insomnia/Postman

---

## Estrutura do Projeto

```
iPagDesafio/
├── docker-compose.yml         # Orquestração dos serviços (PHP, MySQL, RabbitMQ)
├── public/                    # Document root do Apache/PHP (acessível via navegador)
├── src/                       # Código-fonte da aplicação (ex: index.php, controllers, models)
├── db_migrations/             # Scripts de criação/atualização do banco de dados (SQL)
├── migrations/                # (Reservado para futuras migrações automatizadas)
├── tests/                     # Testes automatizados
├── README.md                  # Documentação principal do projeto
```

**Descrição das pastas:**
- `public/`: arquivos públicos acessíveis via web (ex: index.php, assets)
- `src/`: código-fonte da aplicação PHP
- `db_migrations/`: scripts SQL para versionamento do banco de dados
- `migrations/`: reservado para futuras migrações automatizadas
- `tests/`: scripts e arquivos de teste

---

## Migrations (Banco de Dados)

Os scripts SQL para criação das tabelas estão em `db_migrations/`:

- **001_create_customers.sql**
```sql
CREATE TABLE customers (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL,
	document VARCHAR(20) NOT NULL,
	email VARCHAR(100) NOT NULL,
	phone VARCHAR(20),
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```
- **002_create_orders.sql**
```sql
CREATE TABLE orders (
	id INT AUTO_INCREMENT PRIMARY KEY,
	customer_id INT NOT NULL,
	order_number VARCHAR(30) NOT NULL,
	total_value DECIMAL(10,2) NOT NULL,
	status VARCHAR(20) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (customer_id) REFERENCES customers(id)
);
```
- **003_create_order_items.sql**
```sql
CREATE TABLE order_items (
	id INT AUTO_INCREMENT PRIMARY KEY,
	order_id INT NOT NULL,
	product_name VARCHAR(100) NOT NULL,
	quantity INT NOT NULL,
	unit_value DECIMAL(10,2) NOT NULL,
	FOREIGN KEY (order_id) REFERENCES orders(id)
);
```
- **004_create_notification_logs.sql**
```sql
CREATE TABLE notification_logs (
	id INT AUTO_INCREMENT PRIMARY KEY,
	order_id INT NOT NULL,
	old_status VARCHAR(20) NOT NULL,
	new_status VARCHAR(20) NOT NULL,
	message VARCHAR(255),
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

Para rodar as migrations manualmente:
```sh
docker-compose exec app bash -c "mysql --ssl=0 -h db -uipaguser -pipagpass ipag < /var/www/db_migrations/001_create_customers.sql && mysql --ssl=0 -h db -uipaguser -pipagpass ipag < /var/www/db_migrations/002_create_orders.sql && mysql --ssl=0 -h db -uipaguser -pipagpass ipag < /var/www/db_migrations/003_create_order_items.sql && mysql --ssl=0 -h db -uipaguser -pipagpass ipag < /var/www/db_migrations/004_create_notification_logs.sql"
```

---

## Endpoints REST

### POST /orders
Cria um novo pedido.

**Exemplo de payload:**
```json
{
	"customer": {
		"id": 1,
		"name": "Fulano de Tal",
		"document": "12345678900",
		"email": "fulano@email.com",
		"phone": "11999999999"
	},
	"order": {
		"total_value": 150.00,
		"items": [
			{
				"product_name": "Produto 1",
				"quantity": 2,
				"unit_value": 50.00
			},
			{
				"product_name": "Produto 2",
				"quantity": 1,
				"unit_value": 50.00
			}
		]
	}
}
```

### GET /orders/{order_id}
Consulta um pedido específico.

### PUT /orders/{order_id}/status
Atualiza o status do pedido.

**Exemplo de payload:**
```json
{
	"status": "PAID",
	"notes": "Pagamento confirmado via PIX"
}
```

### GET /orders
Lista pedidos (com filtros opcionais).

### GET /orders/summary
Retorna um resumo estatístico dos pedidos.

---

## Regras de Negócio e Workflow

- Status válidos: `PENDING`, `WAITING_PAYMENT`, `PAID`, `PROCESSING`, `SHIPPED`, `DELIVERED`, `CANCELED`
- Transições válidas: status só pode avançar sequencialmente (exceto cancelamento)
- Não é possível cancelar pedidos já entregues
- Toda mudança de status deve publicar mensagem na fila `order_status_updates` do RabbitMQ
- O Worker consome a fila, valida, registra log em `notification_logs` e simula envio de notificação

---

## Exemplo de Mensagem RabbitMQ
```json
{
	"order_id": "ORD-12345",
	"old_status": "PENDING",
	"new_status": "PAID",
	"timestamp": "2025-08-21T10:30:00Z",
	"user_id": "system"
}
```

---

## Worker Python

O worker Python (`public/worker_health.py`) consome a fila `order_status_updates`, valida mensagens, registra logs e implementa health check, DLQ e graceful shutdown.

**Como executar:**
```sh
docker-compose exec app python public/worker_health.py
```

---

## Testes Automatizados

Testes automatizados com PHPUnit garantem o correto funcionamento dos endpoints REST.

**Como rodar:**
```sh
composer install
php vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/Api/OrdersApiTest.php
```

---

## Diferenciais Implementados

- Validação robusta de dados de entrada
- Logs estruturados no worker Python
- Documentação Swagger/OpenAPI
- Health check para API e worker
- Rate limiting (10 req/min por IP)
- Dead Letter Queue (DLQ)
- Graceful shutdown
- Configuração por ambiente
- Database connection pooling (PDO)
- Métricas/monitoramento básico (Docker, health, painel RabbitMQ)
- Collection Insomnia/Postman para testes

---

## Documentação Swagger/OpenAPI

O arquivo `swagger.yaml` na raiz do projeto pode ser importado em https://editor.swagger.io/ para navegação e teste interativo dos endpoints.

---

## Health Check

- **API:** `GET /health` retorna `{ "status": "ok" }`
- **Worker:** Mensagem de health check ao iniciar e shutdown seguro (Ctrl+C ou SIGTERM)

---

## Rate Limiting

Todos os endpoints possuem rate limiting: 10 requisições por minuto por IP. Excedendo o limite, retorna HTTP 429.

---

## Pooling, Métricas e Monitoramento

- Pooling de conexões: PDO reutiliza conexões ativas.
- Métricas: utilize Docker, painel RabbitMQ (porta 15672) e endpoint `/health` para monitoramento.

---

## Collection Insomnia/Postman

Importe o arquivo `insomnia_collection.json` no Insomnia para testar todos os endpoints da API.

---

## Decisões Técnicas e Observações

- Worker PHP foi descontinuado devido a bug no consumo RabbitMQ em Docker; worker Python é a solução recomendada.
- Todas as decisões, alternativas e bugs estão documentados neste README.

---
4. Execute as requisições conforme desejar

Assim, é possível testar rapidamente todos os fluxos da API também pelo Insomnia.
