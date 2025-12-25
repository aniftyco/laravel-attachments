# Laravel Attachments Documentation

This directory contains comprehensive documentation for the Laravel Attachments package.

## Documentation Structure

### Getting Started
- **[index.md](index.md)** - Documentation home page with overview and quick links
- **[installation.md](installation.md)** - Installation guide and initial setup
- **[configuration.md](configuration.md)** - Complete configuration options reference
- **[upgrade-guide.md](upgrade-guide.md)** - Guide for upgrading between versions

### Core Concepts
- **[single-attachments.md](single-attachments.md)** - Working with single file attachments
- **[multiple-attachments.md](multiple-attachments.md)** - Working with multiple file attachments
- **[validation.md](validation.md)** - File validation rules and examples
- **[storage.md](storage.md)** - Storage disks, folders, and organization

### Advanced Features
- **[cleanup.md](cleanup.md)** - Automatic file cleanup and deletion strategies
- **[urls.md](urls.md)** - URL generation for public and private files
- **[metadata.md](metadata.md)** - Storing and accessing attachment metadata
- **[events.md](events.md)** - Listening to attachment lifecycle events
- **[api-resources.md](api-resources.md)** - Transforming attachments for JSON APIs

### Integrations
- **[filament.md](filament.md)** - Using attachments with Filament Admin
- **[testing.md](testing.md)** - Testing helpers and best practices

### API Reference
- **[api/attachment.md](api/attachment.md)** - Complete Attachment class API reference
- **[api/attachments.md](api/attachments.md)** - Complete Attachments collection API reference
- **[api/configuration.md](api/configuration.md)** - Configuration options reference

## Quick Navigation

### I want to...

**Get started quickly**
→ Start with [installation.md](installation.md)

**Upload a single file (avatar, profile picture)**
→ See [single-attachments.md](single-attachments.md)

**Upload multiple files (gallery, documents)**
→ See [multiple-attachments.md](multiple-attachments.md)

**Validate file uploads**
→ See [validation.md](validation.md)

**Store files on S3 or other cloud storage**
→ See [storage.md](storage.md)

**Automatically delete files when models are deleted**
→ See [cleanup.md](cleanup.md)

**Generate URLs for file downloads**
→ See [urls.md](urls.md)

**Store additional information with files**
→ See [metadata.md](metadata.md)

**Process files after upload (thumbnails, optimization)**
→ See [events.md](events.md)

**Use with Filament Admin**
→ See [filament.md](filament.md)

**Write tests for file uploads**
→ See [testing.md](testing.md)

**Look up a specific method or property**
→ See [api/attachment.md](api/attachment.md) or [api/attachments.md](api/attachments.md)

## Documentation for Docs Sites

This documentation is structured to work well with static site generators like:

- **VitePress** - Vue-powered static site generator
- **Docusaurus** - React-based documentation framework
- **MkDocs** - Python-based documentation generator
- **Docsify** - Lightweight documentation site generator
- **GitBook** - Modern documentation platform

The markdown files use standard formatting and are organized in a clear hierarchy, making them easy to integrate with any documentation platform.

## Contributing to Documentation

When contributing to the documentation:

1. **Keep it simple** - Use clear, concise language
2. **Provide examples** - Show code examples for every concept
3. **Link related topics** - Help users navigate between related docs
4. **Test code examples** - Ensure all code examples actually work
5. **Update the index** - Keep the main index.md up to date

## Feedback

Found an issue with the documentation? Please [open an issue](https://github.com/aniftyco/laravel-attachments/issues) or submit a pull request.

