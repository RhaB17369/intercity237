# Architecture Document — Intercity237
## SEN3244 Software Architecture — Spring 2026 — Engr. TEKOH PALMA

---

## 1. Architecture Style

**Intercity237** uses a **Microservices + Event-Driven Architecture (EDA)**:

- Each business domain (auth, passengers, routes, bookings, tickets) runs in an independent containerised service.
- Services communicate **asynchronously** via RabbitMQ for non-blocking operations (email, SMS notifications).
- Synchronous communication goes through the **Nginx API Gateway** (reverse proxy + routing).

### Justification

| Criterion | Monolith | Chosen: Microservices + EDA |
|-----------|----------|-----------------------------|
| Scalability | Vertical only | Scale individual services independently |
| Fault isolation | One crash = full outage | Isolated failures per service |
| Deployment | One big deploy | Independent CI/CD per service |
| Complexity | Low | Higher — justified by domain size |
| Team autonomy | Shared codebase | Each team owns their service |

---

## 2. Component View (C4 Level 2)

```
┌─────────────────────────────────────────────────────────────────┐
│                        INTERCITY237 SYSTEM                       │
│                                                                   │
│  ┌────────────┐    ┌──────────────────────────────────────────┐  │
│  │  Browser   │───▶│          API GATEWAY (Nginx)             │  │
│  │  /Mobile   │    │  Routes:  /login.php → auth-service      │  │
│  └────────────┘    │           /api/*    → route-service      │  │
│                    │           /admin/*  → passenger-service  │  │
│                    │           /checkout → booking-service    │  │
│                    │           /scan.php → ticket-service     │  │
│                    └──────┬───────────────────────────────────┘  │
│                           │                                       │
│          ┌────────────────┼──────────────────────┐               │
│          ▼                ▼                       ▼               │
│  ┌──────────────┐ ┌──────────────┐ ┌─────────────────────────┐   │
│  │ auth-service │ │passenger-svc │ │     route-service       │   │
│  │ login.php    │ │ index.php    │ │  GET /api/cities        │   │
│  │ register.php │ │ admin/       │ │  GET /api/routes        │   │
│  │ logout.php   │ │ users.php    │ │  GET /api/schedules     │   │
│  └──────┬───────┘ └──────┬───────┘ └────────────┬────────────┘   │
│         │                │                        │               │
│         ▼                ▼                        ▼               │
│  ┌──────────────┐ ┌──────────────┐ ┌─────────────────────────┐   │
│  │booking-svc   │ │ ticket-svc   │ │  notification-service   │   │
│  │ checkout.php │ │ scan.php     │ │  RabbitMQ consumer      │   │
│  │ Payment sim  │ │ QR validate  │ │  Email/SMS dispatch     │   │
│  └──────┬───────┘ └──────────────┘ └────────────▲────────────┘   │
│         │                                         │               │
│         │              ┌──────────────────────────┤               │
│         ▼              │                          │               │
│  ┌──────────────┐ ┌────┴─────────┐               │               │
│  │   MariaDB 11 │ │  RabbitMQ    │───────────────▶│               │
│  │  intercity237│ │  Port 5672   │  booking.confirmed event       │
│  └──────────────┘ └─────────────┘                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Deployment View (Docker Compose / Kubernetes)

```
                     HOST (VPS / Local)
┌──────────────────────────────────────────────────────┐
│                    Docker Network: i237-net           │
│                    Subnet: 172.25.0.0/16              │
│                                                        │
│  Port 80  ──▶  intercity237-gateway  (nginx:alpine)   │
│  Port 9090 ──▶  intercity237-prometheus               │
│  Port 3000 ──▶  intercity237-grafana                  │
│  Port 15672 ──▶  intercity237-rabbitmq (management)   │
│                                                        │
│  INTERNAL ONLY (no host port):                         │
│    intercity237-auth       php:8.2-apache  :80        │
│    intercity237-passengers php:8.2-apache  :80        │
│    intercity237-routes     php:8.2-apache  :80        │
│    intercity237-booking    php:8.2-apache  :80        │
│    intercity237-tickets    php:8.2-apache  :80        │
│    intercity237-notify     php:8.2-cli               │
│    intercity237-db         mariadb:11      :3306      │
│    intercity237-node-exp   prom/node-exporter         │
│                                                        │
│  VOLUMES:  db_data, rabbitmq_data, prometheus_data,   │
│            grafana_data                               │
└──────────────────────────────────────────────────────┘
```

In **Kubernetes (k3s)**: each service has a `Deployment` (2 replicas) + `ClusterIP` Service.

---

## 4. Sequence Diagram — Booking Flow

```
Browser        Gateway        Booking-Service      RabbitMQ     Notification
   │                │                │                │              │
   │──GET /─────────▶               │                │              │
   │◀────200 (home)──               │                │              │
   │                                │                │              │
   │──GET /api/schedules────────────▶                │              │
   │◀────JSON: 14 schedules──────────                │              │
   │                                                 │              │
   │──POST /checkout.php────────────▶                │              │
   │   {schedule_id, passenger, phone, payment}      │              │
   │              │──────────────────▶               │              │
   │              │  Validate payment (MoMo sim.)    │              │
   │              │  INSERT booking                  │              │
   │              │  INSERT ticket (QR token)        │              │
   │              │  UPDATE schedules.seats_booked   │              │
   │              │──publish booking.confirmed──────▶│              │
   │              │◀─202 Accepted────────────────────│              │
   │◀────redirect /ticket/{ref}                      │              │
   │                                                 │──consume────▶│
   │                                                 │             Send
   │                                                 │             SMS/Email
```

---

## 5. Class / Module View (auth-service)

```
┌───────────────────────────────────────────────────────┐
│                   includes/auth.php                    │
├───────────────────────────────────────────────────────┤
│ + h(string): string                 // XSS escape     │
│ + is_logged_in(): bool                                │
│ + is_admin(): bool                                    │
│ + is_superadmin(): bool                               │
│ + is_passenger(): bool                                │
│ + is_agent(): bool                                    │
│ + csrf_token(): string                                │
│ + verify_csrf(): void                                 │
│ + require_login(): void             // redirects      │
│ + require_admin(): void             // redirects      │
│ + require_superadmin(): void        // redirects      │
└───────────────────────────────────────────────────────┘
          ▲ used by
┌─────────┴─────────────────────────────────────────────┐
│  login.php / register.php / logout.php                 │
│  forgot_password.php / reset_password.php              │
└───────────────────────────────────────────────────────┘

┌───────────────────────────────────────────────────────┐
│                  includes/helpers.php                  │
├───────────────────────────────────────────────────────┤
│ + format_money(float): string                         │
│ + format_duration(int): string                        │
│ + validate_phone_cm(string): bool                     │
│ + validate_password(string): bool                     │
│ + generate_booking_ref(): string                      │
│ + calculate_arrival(string, int): string              │
│ + seats_available(int, int): int                      │
│ + is_bookable(string, int): bool                      │
└───────────────────────────────────────────────────────┘
```

---

## 6. Database Schema (ER Diagram)

```
cities                 operators
  id PK                  id PK
  name                   name
  region                 phone
                         email
                           │
buses                      │
  id PK ◀────────────────┘
  operator_id FK
  plate
  model
  capacity

routes                  schedules
  id PK ◀──────────────── route_id FK
  origin_id FK ──▶ cities  bus_id FK ──▶ buses
  destination_id FK        departure_at
  distance_km              arrival_at
  duration_min             seats_total
  base_price               seats_booked
                           status

users                  bookings                  tickets
  id PK ◀─────────────── user_id FK               id PK
  full_name               id PK ◀─────────────── booking_id FK
  email                   reference                qr_token
  username                schedule_id FK ──▶ sch.  scanned_at
  password                passenger_name           scanned_by FK
  role                    amount
  phone                   payment_method
                          status
```

---

## 7. Trade-Offs and Quality Attributes

| Quality Attribute | Decision | Trade-off |
|-------------------|----------|-----------|
| **Scalability** | Microservices → scale booking-service independently during peak hours | Added operational complexity |
| **Availability** | k8s Deployments with 2 replicas + health checks | More resource usage |
| **Security** | CSRF tokens, bcrypt passwords, prepared statements | Slightly more code per endpoint |
| **Maintainability** | Each service owns its DB tables | Cross-service queries not possible (joins require API calls) |
| **Observability** | Prometheus + Grafana + node-exporter | Additional 3 containers |
| **Decoupling** | RabbitMQ for notifications (async EDA) | Eventual consistency — notification may arrive slightly late |
| **Performance** | Nginx as API gateway (static-file fast path, proxy cache possible) | Single point of failure if gateway crashes |

### Pros of Microservices + EDA
- Services can be deployed, scaled, and updated independently
- Failure in notification-service does not break the booking flow
- Each service has a clear bounded context
- Technology heterogeneity: services can evolve independently

### Cons
- Distributed tracing is harder than in a monolith
- Network latency between services adds overhead
- Running 12 containers locally requires significant RAM (≈2 GB)
- Data consistency across services requires careful event design

---

## 8. Architecture Decision Records (ADR)

### ADR-001: PHP 8.2 + Apache over Node.js/Python
**Decision**: Use PHP 8.2 with Apache as runtime.
**Reason**: Team expertise, fast development cycle, native PDO for MariaDB, easy Dockerisation with `php:8.2-apache`.

### ADR-002: RabbitMQ over direct DB polling for notifications
**Decision**: Publish `booking.confirmed` events to RabbitMQ.
**Reason**: Decouples the booking write path from email/SMS latency. Improves perceived response time for the user.

### ADR-003: Nginx as API Gateway (not Kong/Traefik)
**Decision**: Plain Nginx with regex location blocks.
**Reason**: Zero dependencies, smallest image, sufficient for routing and reverse proxy at this scale.

### ADR-004: k3s over full Kubernetes
**Decision**: Use k3s for Kubernetes orchestration.
**Reason**: k3s uses ≈50% less RAM than a full kubeadm cluster, suitable for VPS deployment with limited resources.
