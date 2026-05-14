# Gold Manager v2 Architecture

## Goals

- Modernize the legacy football manager application.
- Move from procedural PHP 7.4 patterns to a layered architecture.
- Separate domain logic from presentation.
- Introduce migrations, testing, APIs, services and reusable UI components.
- Make the platform scalable for multiplayer gameplay and future mobile clients.

## Proposed stack

### Backend

- PHP 8.3
- Yii2
- REST API modules
- Service layer
- Repository pattern
- Queue support
- JWT authentication

### Frontend

- Bootstrap 5
- Responsive admin layout
- Progressive enhancement with AJAX
- Optional future Vue 3 integration

### Infrastructure

- Docker Compose
- Nginx
- PHP-FPM
- MariaDB/MySQL
- Redis

## Application layers

### Presentation layer

Responsible for:

- HTML rendering
- Forms
- Validation feedback
- Client-side interactions

### API layer

Responsible for:

- JSON APIs
- Mobile compatibility
- Internal integrations

### Domain layer

Contains:

- Game rules
- Match calculations
- Economy calculations
- Team management logic
- Ranking updates

### Persistence layer

Contains:

- ActiveRecord models
- Repositories
- Migrations
- Query abstractions

## Domain modules

- Users
- Teams
- Players
- Competitions
- Seasons
- Fixtures
- Matches
- Standings
- Market
- Finance
- Stadium
- Training
- Notifications
- Administration

## Coding conventions

- Strict typing whenever possible
- PHPDoc required
- Thin controllers
- Reusable services
- No business logic inside views
- Docker-first development
- Feature-driven development

## Long term goals

- Multiplayer support
- Match simulation engine
- AI-controlled teams
- Mobile application
- Real-time notifications
- WebSocket live matches
