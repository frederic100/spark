# Spark

Use Spark to bootstrap a PHP project from scratch. It sets up the essentials for practicing TDD and maintaining high code quality.

## Install

```bash
git clone git@github.com:frederic100/spark.git
cd spark
./install
```

## Contributing

### Requirements

* docker
* git

### Add a .env.local file

To install locally or on a development server, be careful with the following environment variables:
* DATA_PATH: path where data is stored; must be inside the project (default: ./data/spark)
* DATA_PATH_STORE: path for backups; generally outside the project (default: ../data/spark)
* REMOVE_DATABASE_WHEN_INSTALL: remove database during install (default: false)
* BUILD_WHEN_INSTALL: build application during install (default: false)
* DOCKER_DEV: run development-specific containers (default: false)
* DOCKER_PHP_BUILT_IMAGE: application prebuilt Docker image
* OPTIONAL_VOLUME: mount a local volume for localhost development (default: empty)
* LOCALDEV_WORKING_DIR: working directory useful for development (default: undefined)
* URL_API: override the base URL used by internal API clients (default: empty)
* PULL_POLICY: policy for pulling the PHP built image on start (default: missing)
* HOST_IP : expose a specific IP (for instance with Windonws / WSL set with 0.0.0.0 to fix navigator container network access issue)
* CORS_ALLOWED_ORIGINS: list of servers allowed to request (default: ["https://prod.your-domaine.ltd"])

Typical local development .env.local:

```
HOST_IP=0.0.0.0
DATA_PATH=./data/spark
DATA_PATH_STORE=./data/spark
REMOVE_DATABASE_WHEN_INSTALL=true
BUILD_WHEN_INSTALL=true
DOCKER_DEV=true
OPTIONAL_VOLUME=.:/var/spark
LOCALDEV_WORKING_DIR=true
URL_API=http://nginx
PULL_POLICY=never
```

Typical server development .env.local:
```
DATA_PATH=./data/spark
DATA_PATH_STORE=../data/spark
REMOVE_DATABASE_WHEN_INSTALL=true
DOCKER_DEV=true
DOCKER_PHP_BUILT_IMAGE=gitlab.logipro.com:5050/logipro-fr/captain-learning/captain-learning/captain-learning-php-dev:latest
URL_API=https://dev.your-app.tld
CORS_ALLOWED_ORIGINS=["http://localhost:35081"]
```

For production and pre-production, a .env.local MUST NOT exist because default variables target the production environment.
However, this project includes a frontend that calls the API, so in pre-production you need a .env.local to override `URL_API`.

Typical pre-production .env.local:
```
URL_API=https://preprod.your-app.tld
CORS_ALLOWED_ORIGINS=["https://preprod.your-app.tld"]
```

## Agent

This project is developed using **strict Domain-Driven Design (DDD)** and  
**strict Test-Driven Development (TDD)**.

The development workflow is guided by the instructions in `AGENTS.md`. This file defines how the Cursor AI agent should behave, ensuring consistency with DDD and TDD principles.

### How to Use the Agent

#### Step 1 — Open Cursor Chat
Open the Cursor chat panel in your editor (usually `Ctrl+L` or `Cmd+L`).

#### Step 2 — Initialize the Session
At the beginning of each development session, send the following message:

```
Follow the instructions defined in AGENTS.md strictly.
Do not write any code yet.
Acknowledge and confirm before proceeding.
```

The agent will acknowledge the rules and confirm it understands the constraints before starting.

#### Step 3 — Work with the Agent

**What the agent will do:**
- ✅ Propose the smallest logical step before implementing
- ✅ Write tests first (RED phase) before any production code
- ✅ Explain the reasoning for each step (3-6 lines)
- ✅ List all files created or modified
- ✅ Stop and wait for your validation between steps
- ✅ Respect strict layer separation (Domain → Application → Infrastructure)
- ✅ Use domain-first design (no premature infrastructure)

**What the agent will NOT do:**
- ❌ Write production code without a failing test first
- ❌ Mix domain and infrastructure concerns
- ❌ Anticipate future requirements (YAGNI)
- ❌ Skip explanations or proceed without validation
- ❌ Invent business rules without asking

#### Step 4 — Typical Workflow

1. **Request a feature**: Describe what you want to build
   ```
   I need to add a feature to create a new user account.
   ```

2. **Agent proposes**: The agent will ask clarifying questions (max 5) if needed, then propose the first step

3. **Agent implements**: Following TDD (RED → GREEN → REFACTOR)
   - Writes a failing test
   - Implements minimal code to pass
   - Refactors if needed
   - Lists all files changed

4. **You validate**: Review the changes, run tests, then approve or request modifications

5. **Repeat**: The agent proposes the next step, and the cycle continues

#### Best Practices

- **Be specific**: The clearer your request, the better the agent's response
- **Review each step**: Don't rush—validate tests pass and code quality before continuing
- **Ask questions**: If something is unclear, ask the agent to explain
- **Enforce rules**: If the agent violates DDD/TDD principles, point it out
- **Run quality checks**: Use `./codecheck` regularly to ensure standards are met

#### Example Session

```
You: Follow the instructions defined in AGENTS.md strictly. Do not write any code yet. Acknowledge and confirm before proceeding.

Agent: I acknowledge the rules in AGENTS.md. I understand I must:
- Follow strict TDD (test first, always)
- Respect DDD layer separation
- Propose small steps and wait for validation
- Not mix domain and infrastructure
Ready to proceed when you are.

You: I need to add validation that a tenant name cannot be empty.

Agent: [Proposes step, writes test, implements, lists files]
```

## Tests

### Unit tests

```bash
bin/phpunit
```

We use Test-Driven Development (TDD) principles and good practices.

### Integration tests
Integration tests are all test categories other than unit tests.

```bash
bin/integration
```

### Acceptance tests
Acceptance tests are integration tests that verify the application against feature specifications.
Gherkin is the specification language; Behat is the PHP runner.

```bash
bin/behat
```


## Manual tests

```bash
./start
```
Then open http://127.0.0.1:35080/ in your browser.

```bash
./stop
```

## Quality

Some indicators we aim for:

* phpcs PSR12
* phpstan level 10
* coverage >=100%
* infection MSI >=100%

Quick check:
```bash
./codecheck
```

Check coverage:
```bash
bin/phpunit --coverage-html var
```
Then open `var/index.html` in your browser.

Check infection:
```bash
bin/infection
```
Then open `var/infection.html` in your browser.

