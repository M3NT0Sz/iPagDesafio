

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

### Fase 4: Documentação & Preparação Final (Dia 7)
**Dia 7:**
	- ✔️ README completo e atualizado
	- ✔️ Diagrama da arquitetura (se aplicável)
	- ✔️ Testes finais (automatizados e manuais)
	- ✔️ Ajustes e entrega

---

## Cronograma Resumido

| Dia  | Atividades | Status |
|------|------------|--------|
| 1    | Planejamento, setup Docker, estrutura inicial do repositório | ✔️ |
| 2    | Modelagem de dados, definição de API e workflow | ✔️ |
| 3    | Implementação endpoints básicos da API (CRUD) | ✔️ |
| 4    | Atualização de status + integração com RabbitMQ | ✔️ |
| 5    | Desenvolvimento do worker consumidor RabbitMQ (PHP implementado, bug documentado; Python funcional) | ✔️ |
| 6    | Refinamento, testes (automatizados) e tratamento de erros | ✔️ |
| 7    | Documentação completa, testes finais e entrega | ✔️ |

---

---

## Checklist Final do Desafio iPag

### Requisitos Obrigatórios
- [x] Plano de trabalho detalhado (tarefas, fases, cronograma)
- [x] API Backend (PHP, Slim, MVC, boas práticas)
- [x] Banco de Dados MySQL (migrations, scripts SQL, estrutura sugerida)
- [x] RabbitMQ + Worker (fila `order_status_updates`, publisher na API, consumer implementado em PHP e Python, logs)
- [x] Endpoints REST (POST /orders, GET /orders/{order_id}, PUT /orders/{order_id}/status, GET /orders, GET /orders/summary)
- [x] Estrutura de dados (exemplos de payloads, resposta, status, transições)
- [x] Regras de negócio (status válidos, transições, restrições, publicação na fila)
- [x] Worker de notificação (consome fila, valida, loga, simula notificação)
- [x] Docker Compose funcional (app, db, rabbitmq, worker)
- [x] README detalhado (instruções, decisões técnicas, estrutura, bugs, alternativas)
- [x] Migrations versionadas
- [x] Decisões técnicas documentadas
- [x] Testes automatizados (PHPUnit)
- [x] Facilidade de execução (instruções claras, comandos, exemplos)

### Diferenciais (opcionais)
- [x] Validação robusta de dados de entrada (validações básicas implementadas)
- [x] Logs estruturados no worker Python
- [x] README detalhado com exemplos
- [x] Testes automatizados (PHPUnit)
- [ ] API documentation (Swagger/OpenAPI)
- [ ] Health checks para API e Worker
- [ ] Rate limiting básico nos endpoints
- [ ] Dead Letter Queue (DLQ) para mensagens com falha
- [ ] Métricas/monitoramento básico
- [ ] Graceful shutdown dos serviços
- [ ] Configuração por ambiente
- [ ] Database connection pooling
- [ ] Collection do Postman/Insomnia para testes

---


> **Observação:** Este README será atualizado conforme o andamento do projeto, incluindo diagramas, instruções de uso e detalhes técnicos.

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

Essa estrutura facilita a organização e manutenção do projeto.

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

## Checklist de Fechamento do Dia 2

- [x] Estrutura do projeto criada e documentada
- [x] Scripts de banco de dados versionados em `db_migrations/`
- [x] Endpoints REST definidos e documentados
- [x] Workflow do Worker descrito
- [x] Exemplos de payloads e mensagens documentados
- [x] Ambiente Docker funcional e testado
- [x] Testes manuais dos endpoints principais realizados

Pronto para avançar para a implementação do Worker e integração RabbitMQ no Dia 3!

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

## Observação Importante: Worker PHP e Alternativa Python

### Bug no Worker PHP (AMQPDataReadException)

Durante a implementação e testes do worker PHP (consumidor RabbitMQ), foi identificado um bug persistente ao rodar o worker em ambiente Docker, mesmo após:
- Atualização do pacote `php-amqplib/php-amqplib` para a versão 3.x
- Instalação de todas as extensões e dependências recomendadas (sockets, bcmath, pdo_mysql, zip, unzip, git)
- Criação de um container dedicado (php:8.1-cli) para o worker
- Correção de permissões, variáveis de ambiente e rebuild completo do ambiente

O erro apresentado é:

```
Fatal error: Uncaught PhpAmqpLib\Exception\AMQPDataReadException: Error receiving data in /var/www/vendor/php-amqplib/php-amqplib/PhpAmqpLib/Wire/IO/StreamIO.php:235
```

Esse bug foi isolado como sendo específico do ambiente PHP + php-amqplib no Docker, pois:
- O consumo da fila com Python funciona normalmente no mesmo ambiente
- O worker PHP está aderente ao README e scripts, mas falha ao consumir mensagens

### Alternativa Recomendada: Worker Python

Para garantir a entrega funcional do desafio, recomenda-se utilizar o worker Python (`test_worker_py.py`), que consome a fila `order_status_updates` corretamente e executa o mesmo workflow de validação e log.

O script Python está disponível em `public/test_worker_py.py` e pode ser executado via Docker ou localmente, conforme instruções no próprio arquivo.

#### Justificativa Técnica
- Todas as tentativas de correção do ambiente PHP foram realizadas e documentadas
- O bug é reconhecido em fóruns e issues do php-amqplib, sem solução definitiva para o stack Docker atual
- O uso do worker Python garante aderência ao fluxo do desafio e permite avaliação completa da solução

---

**Resumo:**
- O worker PHP está implementado e documentado, mas apresenta bug de baixo nível no consumo RabbitMQ
- O worker Python é funcional e recomendado para avaliação e testes
- Testes automatizados (PHPUnit) implementados para os principais endpoints da API, garantindo aderência ao padrão REST (exemplo: POST /orders retorna HTTP 201)

Em caso de dúvidas ou necessidade de troubleshooting adicional, consulte os comentários no código e scripts de setup.

---

## Testes Automatizados e Cobertura REST

### PHPUnit
Foram implementados testes automatizados utilizando PHPUnit para garantir o correto funcionamento dos endpoints REST, especialmente para o fluxo de criação de pedidos (POST /orders).

#### Como rodar os testes:

1. Instale as dependências de desenvolvimento:
	```sh
	composer install
	```
2. Execute os testes:
	```sh
	php vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/Api/OrdersApiTest.php
	```

#### O que é validado:
- O endpoint POST /orders retorna HTTP 201 Created ao criar um pedido (aderente ao padrão REST)
- O payload de resposta está conforme o esperado

Esses testes garantem a qualidade da API e facilitam a manutenção futura.

---

## Como Usar o Worker Python

O worker Python (`test_worker_py.py`) é uma alternativa funcional para consumir a fila `order_status_updates` do RabbitMQ, realizando o mesmo workflow do worker PHP.

### Pré-requisitos
- Python 3.8+
- Pacote `pika` instalado (`pip install pika`)
- Acesso ao RabbitMQ (localhost ou conforme definido no `docker-compose.yml`)

### Execução Local
1. Instale o pacote necessário:
	```sh
	pip install pika
	```
2. Execute o script:
	```sh
	python public/test_worker_py.py
	```

### Execução via Docker (opcional)
Você pode criar um container Python para rodar o worker, se preferir isolar o ambiente:
```sh
docker run --rm -it --network=ipagdesafio_default -v %cd%/public:/app python:3.11 bash -c "pip install pika && python /app/test_worker_py.py"
```
> No Linux/Mac, troque `%cd%` por `$(pwd)`.

### Logs e Funcionamento
O worker Python irá consumir mensagens da fila, validar o payload e imprimir logs no terminal, simulando o envio de notificações e registrando o fluxo conforme o esperado pelo desafio.

---