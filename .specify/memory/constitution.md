# YouTube Player Constitution
<!--
Sync Impact Report - Constitution Version 1.1.0
===============================================
Version Change: 1.0.0 → 1.1.0 (Minor amendment)
Rationale: Added new principle V (Language & Localization) requiring Traditional Chinese for all user-facing documentation and specifications

Modified Principles: None

Added Sections:
- Principle V: Language & Localization Standards (new)

Removed Sections: None

Templates Status:
⚠ .specify/templates/plan-template.md - Requires update for language requirements
⚠ .specify/templates/spec-template.md - Requires update for language requirements
⚠ .specify/templates/tasks-template.md - May require language compliance checks
⚠ specs/001-youtube-loop-player/ - Existing documentation may need translation

Follow-up TODOs:
- Review and update existing documentation to Traditional Chinese
- Update templates to enforce language requirements
- Add language validation to quality gates
-->

## Core Principles

### I. Code Quality First (NON-NEGOTIABLE)

**All code MUST:**
- Follow single responsibility principle - one function/class does one thing well
- Maintain clean separation of concerns - UI, business logic, and data access layers clearly separated
- Use meaningful names that reveal intent - no abbreviations except widely accepted conventions
- Keep functions under 50 lines and classes under 300 lines
- Avoid code duplication - extract shared logic into reusable utilities
- Include inline comments only when explaining "why", never "what"

**Rationale**: Code is read far more often than written. High-quality, maintainable code reduces bugs, accelerates feature development, and enables team scalability. This principle is non-negotiable because technical debt compounds exponentially.

### II. Test-Driven Development (NON-NEGOTIABLE)

**Testing discipline MUST be followed:**
- Tests written BEFORE implementation (Red-Green-Refactor cycle strictly enforced)
- Unit tests MUST cover all business logic with minimum 80% coverage
- Integration tests MUST verify all API contracts and service interactions
- Contract tests MUST be written for all public APIs and interfaces
- Tests MUST be independent, fast (<1s per unit test), and deterministic
- Test failures block all commits - no exceptions

**When tests are NOT required**: Proof-of-concept branches explicitly marked `poc/` may defer tests until promoted to feature branches.

**Rationale**: TDD catches bugs before they exist, serves as living documentation, enables confident refactoring, and ensures every feature is testable by design. Non-negotiable because untested code is legacy code from day one.

### III. User Experience Consistency

**All user-facing features MUST:**
- Follow established design patterns and component library
- Provide immediate feedback for all user actions (<100ms visual response)
- Handle errors gracefully with clear, actionable error messages
- Support keyboard navigation and screen reader accessibility (WCAG 2.1 Level AA minimum)
- Maintain consistent visual language (typography, colors, spacing, animations)
- Work seamlessly across target platforms (desktop, mobile web, native apps as applicable)

**User testing requirements**:
- Every user story MUST include acceptance scenarios testable by non-developers
- Critical user journeys MUST be validated with at least 3 users before release
- Confusing UX patterns MUST be documented with rationale or redesigned

**Rationale**: Inconsistent UX creates cognitive load, reduces user confidence, and increases support burden. Users judge quality by interface consistency and responsiveness.

### IV. Performance as a Feature

**Performance targets MUST be defined and measured:**
- Page/screen load time: <2s on 3G connection
- Time to interactive: <3s for critical paths
- API response time: p95 <500ms, p99 <1s
- Memory footprint: <200MB for mobile, <500MB for desktop
- Bundle size: <500KB initial load (web), incremental loading for large assets
- Database query time: <100ms for common operations

**Monitoring requirements**:
- All performance-critical code paths MUST be instrumented
- Performance regression tests MUST run in CI/CD pipeline
- Real user monitoring (RUM) data MUST inform optimization priorities
- Performance budgets MUST be defined in plan.md for every feature

**When to defer optimization**: Premature optimization is prohibited unless performance targets are explicitly defined in spec.md. Profile first, optimize second.

**Rationale**: Performance impacts user satisfaction, conversion rates, and operational costs. Defining performance as a measurable feature ensures it receives equal priority with functional requirements.

### V. Language & Localization Standards (NON-NEGOTIABLE)

**All user-facing content MUST be in Traditional Chinese (zh-TW):**
- Specifications (spec.md) MUST be written in Traditional Chinese
- Planning documents (plan.md) MUST be written in Traditional Chinese
- User documentation (quickstart.md, README.md) MUST be written in Traditional Chinese
- User interface text, labels, and messages MUST be in Traditional Chinese
- Error messages and user feedback MUST be in Traditional Chinese
- Commit messages and PR descriptions SHOULD be in Traditional Chinese or English

**Exceptions (English is permitted):**
- Source code (variables, functions, classes, comments)
- Technical configuration files (package.json, vite.config.js, etc.)
- API contracts and technical specifications in contracts/ directory
- Third-party library documentation references
- Internal development notes and ADRs (Architecture Decision Records)

**Translation requirements**:
- Technical terms MUST use standard Traditional Chinese translations where established
- When no standard translation exists, include English term in parentheses on first use
- Maintain glossary of technical terms in docs/ directory for consistency

**Rationale**: Ensuring all user-facing documentation and specifications are in Traditional Chinese makes the project accessible to the target audience, reduces miscommunication, and maintains cultural relevance. Code remains in English following global software development standards.

## Development Standards

### Code Review Requirements

All code changes MUST:
- Pass automated linting and formatting checks
- Include tests that cover new/changed functionality
- Be reviewed and approved by at least one other developer
- Pass all CI/CD quality gates (tests, coverage, performance benchmarks)
- Update relevant documentation (API docs, quickstart guides, architecture diagrams)

### Documentation Standards

Documentation MUST be:
- Written concurrently with code (not deferred)
- Technology-agnostic where possible (focus on "what" and "why", not "how")
- Versioned alongside code
- Kept up-to-date (outdated docs are worse than no docs)

Required documentation:
- API contracts for all public interfaces (contracts/ directory)
- Architecture decision records (ADRs) for significant design choices
- Quickstart guides testable by new developers
- Data models with entity relationships clearly documented

## Quality Gates

### Pre-Commit Gates
- All tests pass locally
- Code formatted per project standards
- No linting errors or warnings

### CI/CD Gates
- All automated tests pass (unit, integration, contract)
- Code coverage meets minimum threshold (80%)
- Performance benchmarks within budget
- Security scans show no critical/high vulnerabilities
- Build artifacts under size limits

### Pre-Release Gates
- All acceptance scenarios validated
- Performance targets met in staging environment
- Accessibility audit passed (automated + manual)
- User testing completed for critical journeys (if applicable)
- Documentation reviewed and updated
- All user-facing documentation verified in Traditional Chinese

## Governance

### Constitutional Authority
This constitution supersedes all other development practices and guidelines. In case of conflict between this constitution and any other document (coding standards, team norms, etc.), this constitution takes precedence.

### Amendment Process
**Amendments require:**
1. Written proposal documenting: what changes, why needed, impact analysis
2. Review by all active contributors
3. Approval by project maintainer(s)
4. Update to dependent templates (plan, spec, tasks) to maintain consistency
5. Version increment per semantic versioning rules

### Compliance Verification
All pull requests and code reviews MUST verify:
- Adherence to all five core principles
- Completion of all quality gates
- Justification for any complexity introduced (documented in plan.md Complexity Tracking section)
- Language requirements compliance for user-facing documentation

### Complexity Justification Policy
Any pattern or architecture that violates YAGNI (You Aren't Gonna Need It) MUST be justified:
- Document why simpler alternative is insufficient
- Demonstrate specific problem being solved
- Include removal plan if assumption proves wrong

Use plan.md Complexity Tracking table to document justified violations.

**Version**: 1.1.0 | **Ratified**: 2025-10-22 | **Last Amended**: 2025-10-27
