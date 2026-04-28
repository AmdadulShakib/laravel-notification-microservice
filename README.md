# 🔔 Laravel Notification Microservice

A **production-ready**, **scalable** Notification Microservice built with **Laravel**, designed to handle **50,000+ daily notifications** across SMS, Email, and WhatsApp channels with asynchronous processing, AI-ready data logging, and event-driven architecture.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Features](#features)
- [Setup Instructions](#setup-instructions)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Scaling Strategy](#scaling-strategy)
- [Design Decisions & Assumptions](#design-decisions--assumptions)

---

## Overview

This microservice is a core component of a larger system ecosystem, responsible for:

- **Sending notifications** via SMS, Email, and WhatsApp channels (simulated)
- **Asynchronous processing** using Laravel Queue with database driver
- **AI/ML training data** generation with structured logging
- **Event-driven architecture** with Kafka (mock implementation)
- **Circuit breaker pattern** to prevent cascading failures
- **JWT-based API authentication** for service-to-service communication

---

## Architecture

```
                                    ┌─────────────────────────────────┐
                                    │         API Gateway             │
                                    │      (JWT Middleware)           │
                                    └──────────┬──────────────────────┘
                                               │
                                    ┌──────────▼──────────────────────┐
                                    │    NotificationController       │
                                    │    (Thin Controller Layer)      │
                                    └──────────┬──────────────────────┘
                                               │
                              ┌────────────────▼────────────────────┐
                              │       NotificationService           │
                              │     (Business Logic Layer)          │
                              └──────┬──────────────┬───────────────┘
                                     │              │
                    ┌────────────────▼──────┐  ┌───▼───────────────┐
                    │  NotificationRepository│  │  Queue (Database) │
                    │   (Data Access Layer)  │  │  SendNotification │
                    └────────────────┬──────┘  │       Job         │
                                     │         └───┬───────────────┘
                              ┌──────▼──────┐      │
                              │   MySQL DB  │      │
                              └─────────────┘   ┌──▼──────────────────┐
                                                │  Channel Resolution │
                                                ├─────────────────────┤
                                                │ • SmsChannel (85%)  │
                                                │ • EmailChannel (90%)│
                                                │ • WhatsApp (80%)    │
                                                └──┬──────────────────┘
                                                   │
                                    ┌──────────────▼──────────────────┐
                                    │      Circuit Breaker            │
                                    │  CLOSED → OPEN → HALF_OPEN     │
                                    └──────────────┬──────────────────┘
                                                   │
                              ┌────────────────────▼──────────────────┐
                              │          Events & Listeners           │
                              │  NotificationSent / NotificationFailed│
                              └────┬──────────────────────┬───────────┘
                                   │                      │
                         ┌─────────▼───────┐  ┌──────────▼──────────┐
                         │  AI Log (DB)    │  │  Kafka Producer     │
                         │  Training Data  │  │  (Mock/Simulated)   │
                         └─────────────────┘  └─────────────────────┘
```

### Design Patterns

| Pattern | Implementation |
|---------|---------------|
| **Controller → Service → Repository** | Clean separation of concerns |
| **DTO (Data Transfer Object)** | `NotificationDTO`, `NotificationResponseDTO` |
| **Interface Segregation** | Repository interfaces with bindings |
| **Strategy Pattern** | Channel resolution (`SmsChannel`, `EmailChannel`, `WhatsAppChannel`) |
| **Circuit Breaker** | Prevents cascading failures per channel |
| **Observer Pattern** | Events & Listeners for AI logging |
| **Factory Method** | `NotificationService::resolveChannel()` |

---

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Framework | Laravel 13.x (PHP 8.4) |
| Database | MySQL 8.0 |
| Queue | Database Driver (Redis-ready) |
| Cache | Database / Array (testing) |
| Auth | Mock JWT (Bearer Token) |
| Containerization | Docker + Docker Compose |
| Event Streaming | Kafka (Mock) |
| Testing | PHPUnit 12 |

---

## Features

### Core Features
- ✅ **POST /api/v1/notifications/send** — Queue notifications for async delivery
- ✅ **Status Tracking** — Real-time notification status (`pending` → `processing` → `sent`/`failed`)
- ✅ **Multi-Channel** — SMS, Email, WhatsApp (simulated)
- ✅ **Retry Mechanism** — Max 3 retries with exponential backoff (10s, 30s, 90s)
- ✅ **JWT Authentication** — Mock API Gateway middleware

### AI & Analytics
- ✅ **Structured AI Logs** — Training-ready data for ML models
- ✅ **GET /api/v1/analytics/training-data** — Paginated, filterable training data export
- ✅ **Dashboard Stats** — Real-time metrics (success rate, response times, channel breakdown)

### Bonus Features
- ✅ **Docker** — Full containerized setup (App, MySQL, Redis, Kafka, Zookeeper)
- ✅ **Rate Limiting** — 60 requests/minute per IP
- ✅ **Circuit Breaker** — CLOSED → OPEN → HALF_OPEN state management
- ✅ **Event-Driven (Kafka Mock)** — notification.created/sent/failed events
- ✅ **Dashboard Stats Endpoint** — GET /api/v1/dashboard/stats
- ✅ **Health Check** — GET /api/v1/health

---

## Setup Instructions

### Option 1: Docker Setup (Recommended)

```bash
# Clone the repository
git clone <repository-url>
cd laravel-notification-microservice

# Copy environment file
cp .env.example .env

# Update .env for Docker
# DB_HOST=mysql
# REDIS_HOST=redis
# QUEUE_CONNECTION=redis

# Build and start containers
docker-compose up -d --build

# Run migrations
docker-compose exec app php artisan migrate

# Start queue worker (already running via docker-compose)
# Queue worker is configured as a separate container
```

### Option 2: Manual Setup (XAMPP/Local)

```bash
# Clone the repository
git clone <repository-url>
cd laravel-notification-microservice

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create MySQL database named 'microservice'
# Update .env with your database credentials

# Run migrations
php artisan migrate

# Start the development server
php artisan serve

# In a separate terminal, start the queue worker
php artisan queue:work --tries=3 --backoff=10,30,90
```

---

## API Documentation

### Authentication

All protected endpoints require a Bearer token. Generate one first:

```bash
# Generate a mock JWT token
POST /api/v1/auth/token
Body: { "service_name": "my-service" }

# Response
{
  "success": true,
  "data": {
    "token": "eyJhbGc...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

Use the token in subsequent requests:
```
Authorization: Bearer <token>
```

### Endpoints

#### 1. Send Notification
```bash
POST /api/v1/notifications/send
Authorization: Bearer <token>
Content-Type: application/json

{
  "user_id": 1,
  "type": "email",          # sms | email | whatsapp
  "recipient": "user@example.com",
  "message": "Your order has been confirmed!",
  "metadata": {
    "campaign_id": "camp-2026",
    "campaign_name": "Order Confirmation",
    "priority": "high"
  }
}

# Response (201)
{
  "success": true,
  "message": "Notification queued successfully.",
  "data": {
    "id": 1,
    "user_id": 1,
    "type": "email",
    "recipient": "user@example.com",
    "status": "pending",
    "retry_count": 0,
    "created_at": "2026-04-27T00:00:00.000000Z"
  }
}
```

#### 2. Get Notification Status
```bash
GET /api/v1/notifications/{id}/status
Authorization: Bearer <token>
```

#### 3. List Notifications (with filters)
```bash
GET /api/v1/notifications?status=sent&type=email&user_id=1&date_from=2026-04-01&date_to=2026-04-30&per_page=20
Authorization: Bearer <token>
```

#### 4. Retry Failed Notification
```bash
POST /api/v1/notifications/{id}/retry
Authorization: Bearer <token>
```

#### 5. AI Training Data
```bash
GET /api/v1/analytics/training-data?type=sms&status=sent&per_page=50
Authorization: Bearer <token>

# Response
{
  "success": true,
  "data": [
    {
      "notification_id": 1,
      "type": "sms",
      "status": "sent",
      "retry_count": 0,
      "response_time_ms": 234,
      "sent_at": "2026-04-27T10:00:00Z",
      "metadata": {"campaign_id": "xyz"}
    }
  ],
  "meta": { "total": 1000, "page": 1, "per_page": 50 }
}
```

#### 6. Dashboard Stats
```bash
GET /api/v1/dashboard/stats
Authorization: Bearer <token>

# Response
{
  "data": {
    "total_notifications": 50000,
    "sent": 45000,
    "failed": 3000,
    "pending": 2000,
    "success_rate": "90%",
    "avg_response_time_ms": 250,
    "by_channel": {
      "sms": { "total": 20000, "sent": 18000, "failed": 2000 },
      "email": { "total": 20000, "sent": 19000, "failed": 1000 },
      "whatsapp": { "total": 10000, "sent": 8000, "failed": 2000 }
    },
    "last_24h": { "total": 5000, "sent": 4500, "failed": 500 }
  }
}
```

#### 7. Health Check
```bash
GET /api/v1/health    # No auth required
```

---

## Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Test Coverage

| Test Suite | Tests | Assertions |
|------------|-------|------------|
| Feature: NotificationApiTest | 11 tests | API endpoints, auth, validation |
| Unit: NotificationServiceTest | 15 tests | DTOs, channels, retry logic, circuit breaker |
| **Total** | **28 tests** | **99 assertions** |

---

## Scaling Strategy

### Horizontal Scaling

1. **Multiple Queue Workers**: Run N workers to process notifications in parallel
   ```bash
   php artisan queue:work --tries=3 --backoff=10,30,90 --max-time=3600
   ```

2. **Redis Queue**: Switch from database to Redis for high-throughput
   ```env
   QUEUE_CONNECTION=redis
   ```

3. **Database Read Replicas**: Separate read/write workloads for analytics queries

4. **Kafka Event Streaming**: Decouple notification creation from downstream processing

### Performance Optimizations

- **Composite database indexes** on frequently queried columns
- **N+1 prevention** with selective eager loading
- **Chunked queries** for large dataset operations
- **Rate limiting** to prevent API abuse (60 req/min)
- **Circuit breaker** to prevent cascading channel failures

### Capacity Planning

The system is designed to handle **50,000+ notifications/day**:
- Queue workers process jobs in parallel
- Database indexes support efficient status/type filtering
- AI log table is append-only (no update overhead)
- Kafka events are fire-and-forget (non-blocking)

---

## Design Decisions & Assumptions

### Decisions

1. **Database Queue over Redis**: Used database queue by default for simpler setup. Redis is fully supported by changing `QUEUE_CONNECTION=redis`.

2. **Mock JWT over Sanctum**: Since this is a microservice (service-to-service communication), a JWT mock is more appropriate than Laravel Sanctum's token-based auth.

3. **Simulated Channels**: No real SMS/Email gateway integration. Each channel simulates network latency and success rates to demonstrate the retry and circuit breaker logic.

4. **Separate AI Log Table**: `notification_logs` is separate from `notifications` to allow independent scaling and avoid impacting notification processing performance.

5. **Event-Driven with Mock Kafka**: Kafka events are logged to a dedicated file. In production, replace `KafkaProducerService` with a real Kafka client.

### Assumptions

- An API Gateway exists in front of this service (JWT validation is simplified)
- The `user_id` field references users in another microservice (no foreign key to users table)
- Notification channels are simulated (no real SMS/Email/WhatsApp provider)
- The system runs behind a load balancer in production
- Database `microservice` already exists in MySQL

---

## Directory Structure

```
app/
├── DTOs/
│   ├── NotificationDTO.php              # Input data encapsulation
│   └── NotificationResponseDTO.php      # API response formatting
├── Events/
│   ├── NotificationSent.php             # Success event
│   └── NotificationFailed.php           # Failure event
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── AuthController.php           # Mock JWT token generation
│   │   ├── AnalyticsController.php      # AI training data & dashboard
│   │   ├── BaseApiController.php        # Response helpers
│   │   └── NotificationController.php   # Core notification endpoints
│   ├── Middleware/
│   │   └── JwtAuthMiddleware.php        # Mock JWT validation
│   └── Requests/
│       └── SendNotificationRequest.php  # Input validation
├── Jobs/
│   └── SendNotificationJob.php          # Async processing with retry
├── Listeners/
│   ├── LogNotificationSent.php          # AI log on success
│   └── LogNotificationFailed.php        # AI log on failure
├── Models/
│   ├── Notification.php                 # Core model with scopes
│   └── NotificationLog.php             # AI training data model
├── Providers/
│   └── RepositoryServiceProvider.php    # Interface bindings
├── Repositories/
│   ├── Interfaces/
│   │   ├── NotificationRepositoryInterface.php
│   │   └── NotificationLogRepositoryInterface.php
│   ├── NotificationRepository.php       # Data access layer
│   └── NotificationLogRepository.php    # AI log data access
└── Services/
    ├── AnalyticsService.php             # Dashboard & training data
    ├── NotificationService.php          # Core business logic
    ├── Channels/
    │   ├── NotificationChannelInterface.php
    │   ├── SmsChannel.php               # Simulated (85% success)
    │   ├── EmailChannel.php             # Simulated (90% success)
    │   └── WhatsAppChannel.php          # Simulated (80% success)
    ├── CircuitBreaker/
    │   └── CircuitBreakerService.php    # Circuit breaker pattern
    └── Kafka/
        ├── KafkaProducerService.php     # Mock event publishing
        └── KafkaConsumerService.php     # Mock event consuming
```

---

## License

MIT License