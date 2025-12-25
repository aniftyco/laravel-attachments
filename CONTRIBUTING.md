# Contributing to Laravel Attachments

Thank you for considering contributing to Laravel Attachments! This document outlines the process for contributing to this package.

## Code of Conduct

Please be respectful and constructive in all interactions. We want to maintain a welcoming and inclusive community.

## How to Contribute

### Reporting Bugs

If you find a bug, please open an issue on GitHub with:

- A clear, descriptive title
- Steps to reproduce the issue
- Expected behavior
- Actual behavior
- Laravel version and package version
- Any relevant code samples or error messages

### Suggesting Features

Feature suggestions are welcome! Please open an issue with:

- A clear description of the feature
- Use cases and benefits
- Any implementation ideas you might have

### Pull Requests

We actively welcome pull requests! Here's how to contribute code:

1. **Fork the repository** and create your branch from `master`
2. **Install dependencies**: `composer install`
3. **Make your changes** following our coding standards
4. **Add tests** for any new functionality
5. **Run the test suite**: `composer test`
6. **Run code style checks**: `composer lint` or `./vendor/bin/pint`
7. **Commit your changes** with clear, descriptive commit messages
8. **Push to your fork** and submit a pull request

## Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/laravel-attachments.git
cd laravel-attachments

# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer lint

# Fix code style issues
./vendor/bin/pint
```

## Coding Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use Laravel conventions and best practices
- Write clear, self-documenting code
- Add PHPDoc blocks for classes and methods
- Use type hints for parameters and return types

## Testing

- Write tests for all new features and bug fixes
- Ensure all tests pass before submitting a PR
- Aim for high test coverage
- Use descriptive test names that explain what is being tested

Example test structure:

```php
it('can create an attachment from an uploaded file', function () {
    // Arrange
    Storage::fake('public');
    $file = UploadedFile::fake()->image('test.jpg');

    // Act
    $attachment = Attachment::fromFile($file, 'public', 'uploads');

    // Assert
    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->exists())->toBeTrue();
});
```

## Documentation

- Update the README.md if you add new features
- Add inline code comments for complex logic
- Update PHPDoc blocks when changing method signatures

## Questions?

If you have questions about contributing, feel free to open an issue or reach out to the maintainers.

## License

By contributing to Laravel Attachments, you agree that your contributions will be licensed under the MIT License.
