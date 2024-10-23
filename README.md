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

The stub files are parsed with Roave/BetterReflection

## Roadmap

- [ ] Use PHP 8.4 new DOM classes
- [ ] DocParser needs to deal with entities that cannot be expanded
- [ ] Set-up CI
- [ ] Set up Static Analysis
- [ ] Set-up Code Style requirements
- [ ] Parsing of stub files
- [ ] Parsing of Documentation sources for constants
  - [ ] Table parsing 
- [ ] Parsing of Documentation sources for functions
- [ ] Parsing of Documentation sources for classes
- [ ] Parsing of Documentation sources for methods
- [ ] Diff between stub and documentation
- [ ] Generate missing functions
- [ ] Generate missing predefined classes with methods
- [ ] Generate missing predefined attributes
- [ ] Generate missing predefined enums
- [ ] Generate missing extension classes with methods
- [ ] Generate missing extension enums
- [ ] Generate improved `versions.xml` files
- [ ] Update functions
- [ ] Update classes and methods
- [ ] Update enums
- [ ] Update attributes
- [ ] Exclusion list
- [ ] Weekly statistics reports (?)
