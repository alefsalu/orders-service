# Contributing to Orders Service

Thank you for your interest in contributing to `orders-service`!

## Code Quality Standards

Before submitting a Pull Request, please ensure your changes adhere to our development standards:

1. **Pure Domain Model**: Under no circumstances should framework annotations (Symfony, Doctrine, etc.) be imported into `src/Domain/`. Enforced via Deptrac.
2. **Static Analysis**: All code must pass PHPStan at `level: max` (Level 9) with 0 errors.
3. **Coding Standards**: Format code with PHP-CS-Fixer using `make format`.
4. **Testing & Coverage**: Maintain at least 80% line test coverage on `src/Domain` and `src/Application`.
5. **Git Hygiene**:
   - Write Conventional Commits (`feat:`, `fix:`, `test:`, `chore:`, `docs:`, `ci:`, `refactor:`).
   - Ensure the guard script passes (`bash scripts/guard.sh`). Never commit `.env` or keys.

## Development Workflow

```bash
# Start containers
make up

# Run linters and static analysis
make lint

# Run all tests
make test
```
