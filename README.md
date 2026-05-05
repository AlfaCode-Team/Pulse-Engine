# Pulse-Engine

> Enterprise-grade, framework-agnostic PHP voting module.  
> Hexagonal architecture · PSR-4 · Typed DTOs · Event-driven · Full migration suite

---

## Architecture

```
src/
├── Config/           VotingConfig — typed, immutable config value object
├── Contract/         All interfaces (Database, Cache, Repositories, Payment, Events, RateLimiter)
├── DTO/              Immutable command + result objects (CastVoteCommand, VoteResult, …)
├── Entity/           Rich domain entities (Contestant, VoteRecord, PaymentRecord)
├── Enum/             PHP 8.1 enums (VoteStatus, ContestantStatus, PaymentStatus)
├── Event/            Domain events + NullEventDispatcher
├── Exception/        Typed exception hierarchy rooted at PulseEngineException
├── Factory/          VoteServiceFactory — wires the full object graph
├── Migration/        SQL migrations + MigrationRunner
├── Repository/       Concrete PDO-backed repository implementations
├── Security/         RateLimiter (cache-backed) + IntegrityGuard (HMAC)
└── Service/          VoteService · PaymentService · PricingEngine · SubscriptionService
```

---

## Quick Start (no DI container)

```php
use AlfaCode\PulseEngine\Config\VotingConfig;
use AlfaCode\PulseEngine\DTO\CastVoteCommand;
use AlfaCode\PulseEngine\Factory\VoteServiceFactory;

$config      = VotingConfig::fromFile(__DIR__ . '/config/voting_config.php');
$voteService = VoteServiceFactory::createVoteService($db, $cache, $config);

$result = $voteService->castFreeVote(new CastVoteCommand(
    userId:       $userId,
    contestantId: $contestantId,
    editionId:    $editionId,
    ipAddress:    $_SERVER['REMOTE_ADDR'],
    userAgent:    $_SERVER['HTTP_USER_AGENT'] ?? '',
));

echo "Vote #{$result->voteId} cast. New total: {$result->newVoteTotal}";
```

---

## Paid Votes Flow

```php
use AlfaCode\PulseEngine\DTO\PurchaseVotesCommand;
use AlfaCode\PulseEngine\Factory\VoteServiceFactory;

$paymentService = VoteServiceFactory::createPaymentService(
    $db, $cache, $config, $paystackProvider
);

// Step 1 — Initiate: returns a redirect URL
$redirectUrl = $paymentService->initiate(
    new PurchaseVotesCommand(
        userId:       $userId,
        contestantId: $contestantId,
        editionId:    $editionId,
        voteCount:    50,
        ipAddress:    $ip,
        callbackUrl:  'https://yourapp.com/payment/callback',
    ),
    userEmail: $user->email,
);

// Step 2 — Verify (on callback / webhook)
$voteResult = $paymentService->verifyAndApply($reference, $ip);
```

---

## Migrations

SQL migrations live in `src/Migration/`. Run them in order:

```
001_create_vote_editions.sql
002_create_vote_contestants.sql
003_create_vote_voting.sql
004_create_vote_payments.sql
005_create_vote_audit_log.sql
```

Or use the PHP runner:

```php
use AlfaCode\PulseEngine\Migration\MigrationRunner;

$runner = new MigrationRunner($db, __DIR__ . '/src/Migration');
$runner->run();           // apply pending
$runner->rollback(1);     // undo last migration
```

---

## Extending

| Need | Implement |
|---|---|
| New payment gateway | `Contract\PaymentProviderInterface` |
| Custom cache backend | `Contract\CacheInterface` |
| PSR-14 event bus | `Contract\EventDispatcherInterface` |
| Different database driver | `Contract\DatabaseInterface` |

---

## Configuration

Copy `config/voting_config.php` outside your repository and point the engine at it:

```php
$config = VotingConfig::fromFile('/etc/pulse-engine/config.php');
```

| Key | Default | Description |
|---|---|---|
| `security.max_per_minute` | `5` | Max votes per IP per window |
| `security.window_seconds` | `60` | Rate-limit window length |
| `security.secret_key` | — | **Required.** HMAC signing key (32+ chars) |
| `pricing.tier1_max` | `20` | Max votes for tier-1 price |
| `pricing.tier1_kobo` | `1000` | Kobo per vote (tier 1) |
| `pricing.tier2_max` | `100` | Max votes for tier-2 price |
| `pricing.tier2_kobo` | `800` | Kobo per vote (tier 2) |
| `pricing.tier3_kobo` | `500` | Kobo per vote (bulk 100+) |
| `features.free_vote` | `true` | Enable free vote per user |

---

## Tests

```bash
composer install
vendor/bin/phpunit
```

---

## License

MIT © 2026 AlfaCode Team
