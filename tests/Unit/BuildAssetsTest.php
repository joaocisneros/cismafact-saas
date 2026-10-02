<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BuildAssetsTest extends TestCase
{
    #[Test]
    public function el_manifiesto_solo_referencia_archivos_compilados_existentes(): void
    {
        $manifestPath = dirname(__DIR__, 2).'/public/build/manifest.json';

        $this->assertFileExists($manifestPath, 'Falta public/build/manifest.json. Ejecuta npm run build.');

        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        foreach ($manifest as $entry => $asset) {
            $this->assertArrayHasKey('file', $asset, "El recurso {$entry} no tiene un archivo compilado.");
            $this->assertFileExists(
                dirname($manifestPath).'/'.$asset['file'],
                "El manifiesto referencia un recurso inexistente: {$asset['file']}"
            );
        }
    }
}
