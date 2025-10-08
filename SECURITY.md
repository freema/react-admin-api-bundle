# Security Policy

## Supported Versions

We release patches for security vulnerabilities for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take the security of React Admin API Bundle seriously. If you believe you have found a security vulnerability, please report it to us as described below.

### Where to Report

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them via email to:

- **Email**: security@freema.cz

Please include the following information in your report:

- Type of issue (e.g., SQL injection, XSS, CSRF, authentication bypass, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit the issue

This information will help us triage your report more quickly.

### What to Expect

After you submit a vulnerability report, we will:

1. **Acknowledge receipt** of your vulnerability report within 48 hours
2. **Investigate and validate** the security issue
3. **Work on a fix** and prepare a security release
4. **Notify you** when the security issue is fixed
5. **Credit you** for the discovery (unless you prefer to remain anonymous)

### Security Update Process

When a security vulnerability is confirmed:

1. We will prepare a fix and create a security advisory
2. We will release a new version with the security patch
3. We will publish a security advisory describing the vulnerability and the fix
4. We will update this SECURITY.md file if necessary

## Security Best Practices

When using React Admin API Bundle in production:

### 1. Authentication & Authorization

Always implement proper authentication and authorization:

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/api, roles: ROLE_ADMIN }
```

### 2. Input Validation

Always validate DTOs using Symfony's validator:

```php
use Symfony\Component\Validator\Constraints as Assert;

class UserDto implements DtoInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;
}
```

### 3. CORS Configuration

Configure CORS properly for production:

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['^https://yourdomain\.com$']
        allow_methods: ['GET', 'POST', 'PUT', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
```

### 4. Rate Limiting

Implement rate limiting to prevent abuse:

```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        api:
            policy: 'sliding_window'
            limit: 100
            interval: '60 minutes'
```

### 5. Error Messages

In production, disable debug mode to prevent information leakage:

```yaml
# config/packages/prod/react_admin_api.yaml
react_admin_api:
    exception_listener:
        debug_mode: false
```

### 6. SQL Injection Prevention

The bundle uses Doctrine ORM with parameterized queries, but always:

- Use the provided filtering mechanisms
- Never concatenate user input directly into query strings
- Use custom filters with proper parameter binding

### 7. XSS Prevention

- Always sanitize output on the frontend
- Use React Admin's built-in field components which handle escaping
- Validate and sanitize rich text fields

### 8. CSRF Protection

Symfony's CSRF protection should be enabled for forms:

```yaml
# config/packages/framework.yaml
framework:
    csrf_protection: true
```

### 9. Secure Headers

Configure secure HTTP headers:

```yaml
# config/packages/nelmio_security.yaml
nelmio_security:
    content_type:
        nosniff: true
    xss_protection:
        enabled: true
        mode_block: true
    clickjacking:
        paths:
            '^/.*': DENY
```

### 10. Keep Dependencies Updated

Regularly update dependencies:

```bash
composer update
composer audit
```

## Known Security Considerations

### Event System

The event system allows modification of data and responses. Ensure event listeners:
- Validate all input data
- Do not expose sensitive information
- Follow the principle of least privilege

### Custom Repositories

When implementing custom repository methods:
- Use parameterized queries
- Validate all input parameters
- Follow Doctrine security best practices

### DTO Factory

The DtoFactory creates DTOs from request data:
- Always validate DTOs after creation
- Use proper type hints
- Implement validation constraints

## Disclosure Policy

When we release a security fix:
- We will credit the reporter (unless they wish to remain anonymous)
- We will publish details only after users have had time to update
- We will follow responsible disclosure practices

## Comments on This Policy

If you have suggestions on how this process could be improved, please submit a pull request or open an issue.
