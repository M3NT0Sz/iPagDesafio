
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
	- Integração com RabbitMQ (em andamento)
	- Testes manuais (parcialmente realizados)

### Fase 3: Implementação do Worker (Dias 5–6)
- **Dia 5:**
	- Desenvolvimento do consumer RabbitMQ
	- Integração com Docker Compose
	- Testes de fluxo completo
- **Dia 6:**
	- Refinamento
	- Tratamento de erros
	- Testes adicionais

### Fase 4: Documentação & Preparação Final (Dia 7)
- **Dia 7:**
	- README completo
	- Diagrama da arquitetura
	- Testes finais
	- Ajustes e entrega

---

## Cronograma Resumido

| Dia  | Atividades | Status |
|------|------------|--------|
| 1    | Planejamento, setup Docker, estrutura inicial do repositório | ✔️ |
| 2    | Modelagem de dados, definição de API e workflow | ✔️ |
| 3    | Implementação endpoints básicos da API (CRUD) | ✔️ |
| 4    | Atualização de status + integração com RabbitMQ | Parcial |
| 5    | Desenvolvimento do worker consumidor RabbitMQ |  |
| 6    | Refinamento, testes e tratamento de erros |  |
| 7    | Documentação completa, testes finais e ajustes finais |  |

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