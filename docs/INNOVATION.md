# Innovation — Intercity237
## Section 9 — Project Innovation (10 Marks)

---

## 1. Problem Addressed

Bus travel between Cameroon's major cities (Douala, Yaoundé, Bafoussam, Bamenda, etc.) suffers from:
- No online booking — passengers must physically queue at bus stations
- No cashless payment — only cash, vulnerable to theft
- Fraudulent paper tickets — no verification at boarding
- No real-time seat availability — overbooking common

**Intercity237 solves all four problems** with a fully digital platform.

---

## 2. Innovation Highlights

### 2.1 Mobile Money Integration (MTN MoMo + Orange Money)

Cameroon's banking penetration is ~15%, but mobile money usage exceeds 70%.
Intercity237 integrates both major payment networks natively:

```
Payment flow:
  1. Passenger enters their MoMo number
  2. System simulates payment approval
  3. Booking is confirmed in <1 second
  4. Ticket QR is generated immediately

Rule: numbers starting with "000" simulate a failed payment
      (insufficient funds / timeout scenario)
```

This mirrors the real MTN MoMo API flow (initiate → callback) using an event-driven pattern via RabbitMQ.

### 2.2 QR Code Ticket System

Instead of a paper ticket, passengers receive a **unique QR token** (`ICY-2026-XXXXXX`):

- Generated with `bin2hex(random_bytes(32))` — cryptographically secure, unique
- Stored in the `tickets` table with `scanned_at` timestamp
- At boarding: agent scans → system marks `scanned_at = NOW()` (prevents reuse)
- QR code rendered via `api.qrserver.com` API — no server-side image library needed

### 2.3 Event-Driven Notification Architecture

When a booking is confirmed, the booking-service publishes a `booking.confirmed` event to RabbitMQ. The notification-service consumes this event **asynchronously** and dispatches confirmations:

```
booking-service ──▶ RabbitMQ ──▶ notification-service ──▶ SMS/Email
(synchronous)       (queue)         (async consumer)        (external)
```

**Business value**: The user receives their ticket immediately (booking is synchronous), while the SMS/email is sent in the background — no waiting for external API latency.

### 2.4 Real-Time Seat Availability

The system tracks `seats_booked` per schedule in real time. Each booking atomically increments the counter. The search results show exact seat availability, preventing overbooking — a major issue with manual systems.

### 2.5 Cameroon-Specific Business Logic

- **10 cities** matching Cameroon's actual intercity bus network (Douala, Yaoundé, Bafoussam, Bamenda, Garoua, Maroua, Ngaoundéré, Bertoua, Ebolowa, Limbe)
- **4 real operators**: Général Express, Buca Voyages, Vatican Express, Touristique Express
- **FCFA currency** formatting throughout
- **Cameroonian phone validation** (9 digits starting with 6, +237 prefix support)

---

## 3. Technical Innovation

### Microservices at Exam Scale

Most student projects at this level use a monolith. Intercity237 deliberately uses microservices to demonstrate:
- **Domain isolation**: auth, passengers, routes, booking, tickets, notifications are fully decoupled
- **Independent scaling**: booking-service can be scaled to 5 replicas during peak hours without touching auth-service
- **Kubernetes-native**: all services have readiness/liveness probes, rolling update support

### Zero-Library QR Generation

Instead of installing a QR library (GD/Imagick), the system uses a REST call to `api.qrserver.com`:

```php
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_token);
```

This keeps the Docker images small (no extra extensions) and the QR codes are displayed as `<img>` tags.

---

## 4. Comparison with Existing Solutions

| Feature | Manual bus station | Intercity237 |
|---------|-------------------|--------------|
| Booking | In-person only | Online 24/7 |
| Payment | Cash only | MTN MoMo + Orange Money |
| Ticket | Paper (fakes possible) | QR code (tamper-proof, single-use) |
| Seat availability | Unknown until at station | Real-time in API |
| Admin oversight | Paper ledger | Live dashboard with revenue metrics |
| Overbooking | Common | Prevented by atomic DB counter |

---

## 5. Future Innovations (Roadmap)

1. **AI-powered demand forecasting** — predict peak routes and dynamically adjust prices
2. **WhatsApp bot booking** — book directly via WhatsApp Business API (common in Cameroon)
3. **Blockchain ticket NFT** — non-fungible ticket on a lightweight chain for absolute anti-fraud
4. **GPS bus tracking** — real-time bus location for passengers waiting at the station

---

*SEN3244 Software Architecture — Engr. TEKOH PALMA — The ICT University — Spring 2026*
