# php-src stub files to DocBook documentation

The purpose of this tool is to parse
[`php-src`](https://github.com/php/php-src)
stub files and generate or update the [`doc-en`](https://github.com/php/doc-en) DocBook sources.

The primary motivation is that having this functionality ties to `gen_stub.php` doesn't make a lot of sense.
As improvements to the documentation must pollute `gen_stub.php` even if there are no benefit to php-src.
Moreover, it has effectively no tests and is tied to the release version.

This is extremely Work In Progress.

## Design philosophy

The high level idea is to provide a CLI tool a path to `php-src` and `doc-en`,
where both stubs and the existing documentation XML sources are parsed into metadata that can easily be compared
for missing symbols, and replace them if required.

The stub files are parsed with [`Roave/BetterReflection`](https://github.com/Roave/BetterReflection)

## Roadmap

- [x] Use PHP 8.4 new DOM classes
- [ ] DocParser needs to deal with entities that cannot be expanded
- [ ] Set-up CI
- [ ] Set-up Static Analysis:
  - [x] PHPStan level 7 (level 8 seems improbable due to DOM hell)
  - [ ] Set-up Psalm?
  - [ ] Set-up Mago?
- [ ] Set-up Code Style requirements
- [x] Parsing of stub files (handled by `Roave/BetterReflection`)
  - Create a `ZendReflector` class that handles filtering out `UNKNOWN` constant from `reflectAllConstants()`?
- [ ] Parsing of Documentation sources for constants
  - [ ] Table parsing (or convert sourced to stop relying on tables?)
  - [ ] Token constant list
  - [ ] Handle Deprecated attribute for global constants
- [x] Parsing of Documentation sources for functions
  - [ ] Handle pages with multiple methodsynopsis
  - [ ] Parsing of Documentation sources for methods
    - [ ] `final` modifier
    - [ ] `static` modifier
    - [ ] Inherited?
- [ ] Parsing of Documentation sources for classes
  - [ ] Properties
- [ ] Diff
  - [ ] Between stub and documentation
    - [x] Constant diff (kinda see `scripts/parse-stub.php`)
    - [ ] Function diff
    - [ ] Class diff
  - [ ] Between different stubs (e.g. 8.4 and 8.5) (see `scripts/migration-diff.php`)
      - [x] Constant diff
      - [x] Function diff
      - [ ] Class diff
      - [ ] Modified symbols
- [ ] Generate missing constants
- [ ] Generate missing functions
- [ ] Generate missing predefined classes with methods
- [ ] Generate missing predefined attributes
- [ ] Generate missing predefined enums
- [ ] Generate missing extension classes with methods
- [ ] Generate missing extension enums
- [ ] Generate improved `versions.xml` files
- [ ] Update constants
- [ ] Update functions
- [ ] Update classes and methods
- [ ] Update enums
- [ ] Update attributes
- [ ] Generate Migration guides
- [ ] Exclusion list
- [ ] Weekly statistics reports (?)
