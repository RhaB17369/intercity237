<?php
/**
 * Intercity237 Notification Service — RabbitMQ Event Consumer
 * Consumes events from the intercity237 exchange and sends notifications.
 */

$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int)(getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'guest';
$pass = getenv('RABBITMQ_PASS') ?: 'guest';

echo "[notification-service] Starting up...\n";

// Retry connection — RabbitMQ may not be ready immediately
$maxRetries = 10;
$connection  = null;

for ($i = 1; $i <= $maxRetries; $i++) {
    try {
        $connection = new AMQPConnection([
            'host'     => $host,
            'port'     => $port,
            'login'    => $user,
            'password' => $pass,
        ]);
        $connection->connect();
        echo "[notification-service] Connected to RabbitMQ at {$host}:{$port}\n";
        break;
    } catch (AMQPConnectionException $e) {
        echo "[notification-service] Attempt {$i}/{$maxRetries} — waiting 5s...\n";
        sleep(5);
    }
}

if (!$connection || !$connection->isConnected()) {
    echo "[notification-service] FATAL: Cannot connect to RabbitMQ after {$maxRetries} attempts.\n";
    exit(1);
}

$channel  = new AMQPChannel($connection);

// Declare the exchange
$exchange = new AMQPExchange($channel);
$exchange->setName('intercity237');
$exchange->setType(AMQP_EX_TYPE_TOPIC);
$exchange->setFlags(AMQP_DURABLE);
$exchange->declare();

// Declare the notification queue
$queue = new AMQPQueue($channel);
$queue->setName('notifications');
$queue->setFlags(AMQP_DURABLE);
$queue->declare();
$queue->bind('intercity237', '#');

echo "[notification-service] Listening for events on exchange 'intercity237'...\n";

$queue->consume(function (AMQPEnvelope $message, AMQPQueue $q) {
    $routingKey = $message->getRoutingKey();
    $body       = json_decode($message->getBody(), true);

    echo "[notification-service] EVENT: {$routingKey}\n";
    echo "[notification-service] PAYLOAD: " . json_encode($body, JSON_PRETTY_PRINT) . "\n";

    switch ($routingKey) {
        case 'user.registered':
            handle_user_registered($body);
            break;

        case 'user.password_reset_requested':
            handle_password_reset($body);
            break;

        case 'department.record.created':
            handle_record_created($body);
            break;

        case 'user.login.failed':
            handle_login_failed($body);
            break;

        default:
            echo "[notification-service] Unhandled event: {$routingKey}\n";
    }

    $q->ack($message->getDeliveryTag());
});

function handle_user_registered(array $data): void {
    $email = $data['email'] ?? 'unknown';
    $name  = $data['full_name'] ?? 'User';
    echo "[notification-service] SEND welcome email to {$email} for user '{$name}'\n";
    // mail($email, "Welcome to Intercity237", "...");
}

function handle_password_reset(array $data): void {
    $email = $data['email'] ?? 'unknown';
    $token = $data['token'] ?? '';
    echo "[notification-service] SEND password reset email to {$email}\n";
    // mail($email, "Password Reset Request", "Reset link: ...");
}

function handle_record_created(array $data): void {
    $dept = $data['department'] ?? 'unknown';
    $emp  = $data['employee_name'] ?? 'unknown';
    echo "[notification-service] NOTIFY admin: new record for '{$emp}' in dept '{$dept}'\n";
}

function handle_login_failed(array $data): void {
    $email    = $data['email'] ?? 'unknown';
    $attempts = $data['attempts'] ?? 0;
    echo "[notification-service] ALERT: {$attempts} failed login attempts for {$email}\n";
}
