<?php

namespace Documentation;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\DocumentedConstantParser;
use PHPUnit\Framework\TestCase;

class DocumentedConstantParserTest extends TestCase
{
    const /* string */ TEST_FILE = <<<'FILE'
<?xml version="1.0" encoding="utf-8"?>
<!-- $Revision$ -->
<sect2 xml:id="reserved.constants.core" xmlns="http://docbook.org/ns/docbook">
 <title>Core Predefined Constants</title>
 <simpara>
  These constants are defined by the PHP core. This includes PHP,
  the Zend engine, and SAPI modules.
 </simpara>
 <variablelist>
  <varlistentry xml:id="constant.php-maxpathlen">
   <term>
    <constant>PHP_MAXPATHLEN</constant>
    (<type>int</type>)
   </term>
   <listitem>
    <simpara>
     The maximum length of filenames (including path) supported
     by this build of PHP.
    </simpara>
   </listitem>
  </varlistentry>
  <varlistentry xml:id="constant.php-os">
   <term>
    <constant>PHP_OS</constant>
    (<type>string</type>)
   </term>
   <listitem>
    <simpara>
     The operating system PHP was built for.
    </simpara>
   </listitem>
  </varlistentry>
  <varlistentry xml:id="constant.php-float-epsilon-is-incorrect">
   <term>
    <constant>PHP_FLOAT_EPSILON</constant>
    (<type>float</type>)
   </term>
   <listitem>
    <simpara>
     Smallest representable positive number x, so that <literal>x + 1.0 !=
     1.0</literal>.
     Available as of PHP 7.2.0.
    </simpara>
   </listitem>
  </varlistentry>
  <varlistentry>
   <term>
    <constant>E_ERROR</constant>
    (<type>int</type>)
   </term>
   <listitem>
    <simpara>
     <link linkend="errorfunc.constants">Error reporting constant</link>
    </simpara>
   </listitem>
  </varlistentry>
 </variablelist>
 <variablelist>
  <title>Other constants constants</title>
  <varlistentry>
   <term>
    <constant>__COMPILER_HALT_OFFSET__</constant>
    (<type>int</type>)
   </term>
   <listitem>
    <simpara>

    </simpara>
   </listitem>
  </varlistentry>
  <varlistentry xml:id="constant.stdout">
   <term>
    <constant>STDOUT</constant>
    (<type>resource</type>)
   </term>
   <listitem>
    <simpara>
     An already opened stream to <literal>stdout</literal>.
     Available only under the CLI SAPI.
    </simpara>
   </listitem>
  </varlistentry>
 </variablelist>
 <para>
  See also: <link linkend="language.constants.magic">Magic
  constants</link>.
 </para>
</sect2>
<!-- Keep this comment at the end of the file
Local variables:
mode: sgml
sgml-omittag:t
sgml-shorttag:t
sgml-minimize-attributes:nil
sgml-always-quote-attributes:t
sgml-indent-step:1
sgml-indent-data:t
indent-tabs-mode:nil
sgml-parent-document:nil
sgml-default-dtd-file:"~/.phpdoc/manual.ced"
sgml-exposed-tags:nil
sgml-local-catalogs:nil
sgml-local-ecat-files:nil
End:
-->

FILE;

    public function test_parsing_xml(): void
    {
        $document = XMLDocument::createFromString(self::TEST_FILE);
        $constants = DocumentedConstantParser::parse($document, 'Core');

        /** Have 2 lists of constants */
        self::assertCount(2, $constants);
        /** Individual lists */
        self::assertCount(4, $constants[0]);
        self::assertArrayHasKey('PHP_MAXPATHLEN', $constants[0]->constants);
        self::assertSame('PHP_MAXPATHLEN', $constants[0]->constants['PHP_MAXPATHLEN']->name);
        self::assertSame('constant.php-maxpathlen', $constants[0]->constants['PHP_MAXPATHLEN']->id);
        self::assertArrayHasKey('PHP_OS', $constants[0]->constants);
        self::assertSame('PHP_OS', $constants[0]->constants['PHP_OS']->name);
        self::assertSame('constant.php-os', $constants[0]->constants['PHP_OS']->id);
        self::assertArrayHasKey('PHP_FLOAT_EPSILON', $constants[0]->constants);
        self::assertSame('PHP_FLOAT_EPSILON', $constants[0]->constants['PHP_FLOAT_EPSILON']->name);
        self::assertNotSame('constant.php-float-epsilon', $constants[0]->constants['PHP_FLOAT_EPSILON']->id);
        self::assertArrayHasKey('E_ERROR', $constants[0]->constants);
        self::assertSame('E_ERROR', $constants[0]->constants['E_ERROR']->name);
        self::assertNull($constants[0]->constants['E_ERROR']->id);

        self::assertCount(2, $constants[1]);
        self::assertArrayHasKey('__COMPILER_HALT_OFFSET__', $constants[1]->constants);
        self::assertSame('__COMPILER_HALT_OFFSET__', $constants[1]->constants['__COMPILER_HALT_OFFSET__']->name);
        self::assertArrayHasKey('STDOUT', $constants[1]->constants);
        self::assertSame('STDOUT', $constants[1]->constants['STDOUT']->name);
    }
}
