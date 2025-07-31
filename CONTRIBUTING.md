# Contributing to Conduit DJ

First off, thank you for considering contributing to Conduit DJ! It's people like you that make Conduit DJ such a great tool.

## Code of Conduct

This project and everyone participating in it is governed by the [Conduit Code of Conduct](https://github.com/conduit-ui/conduit/blob/master/CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

* Use a clear and descriptive title
* Describe the exact steps which reproduce the problem
* Provide specific examples to demonstrate the steps
* Describe the behavior you observed after following the steps
* Explain which behavior you expected to see instead and why
* Include details about your configuration and environment

### Suggesting Enhancements

* Use a clear and descriptive title
* Provide a step-by-step description of the suggested enhancement
* Provide specific examples to demonstrate the steps
* Describe the current behavior and explain which behavior you expected to see instead
* Explain why this enhancement would be useful

### Pull Requests

* Fill in the required template
* Do not include issue numbers in the PR title
* Include screenshots and animated GIFs in your pull request whenever possible
* Follow the PHP coding standards (PSR-12)
* Include thoughtfully-worded, well-structured tests
* Document new code
* End all files with a newline

## Development Process

1. Fork the repo and create your branch from `master`
2. Run `composer install` to install dependencies
3. Create your feature or fix
4. Add tests for your changes
5. Ensure all tests pass with `composer test`
6. Run `composer quality` to ensure code quality
7. Make sure your code follows the style guide with `composer lint`
8. Issue your pull request!

## Testing

```bash
# Run all tests
composer test

# Run specific test groups
./vendor/bin/pest --group=unit
./vendor/bin/pest --group=feature
./vendor/bin/pest --group=integration

# Run with coverage
./vendor/bin/pest --coverage
```

## Coding Standards

We use Laravel Pint for code formatting. Before submitting:

```bash
composer lint
```

## Documentation

* Keep CLAUDE.md updated with architectural changes
* Update README.md for user-facing changes
* Add PHPDoc blocks for all public methods
* Include examples in documentation

## Financial Contributions

We also welcome financial contributions via GitHub Sponsors.

## Credits

### Contributors

Thank you to all the people who have already contributed to Conduit DJ!

### Backers

Thank you to all our backers! 🙏

### Sponsors

Support this project by becoming a sponsor. Your logo will show up here with a link to your website.