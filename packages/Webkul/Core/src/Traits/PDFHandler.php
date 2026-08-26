<?php

namespace Webkul\Core\Traits;

use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Mpdf\Mpdf;

trait PDFHandler
{
    /**
     * Download PDF.
     *
     * @return Response
     */
    protected function downloadPDF(string $html, ?string $fileName = null, string $paper = 'A4')
    {
        if (is_null($fileName)) {
            $fileName = Str::random(32);
        }

        if (in_array($direction = app()->getLocale(), ['ar', 'he'])) {
            $mPDF = new Mpdf([
                'mode'         => 'utf-8',
                'format'       => strtoupper($paper),
                'autoScriptToLang' => true,
                'autoLangToFont'   => true,
                'margin_left'  => 0,
                'margin_right' => 0,
                'margin_top'   => 0,
                'margin_bottom'=> 0,
            ]);

            $mPDF->SetDirectionality('rtl');

            $mPDF->SetDisplayMode('fullpage');

            $mPDF->WriteHTML($html);

            return response()->streamDownload(fn () => print ($mPDF->Output('', 'S')), $fileName.'.pdf');
        }

        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        return Pdf::loadHTML($this->adjustArabicAndPersianContent($html))
            ->setPaper($paper, 'portrait')
            ->set_option('defaultFont', 'Courier')
            ->download($fileName.'.pdf');
    }

    /**
     * Adjust arabic and persian content.
     *
     * @return string
     */
    protected function adjustArabicAndPersianContent(string $html)
    {
        $arabic = new Arabic;

        $p = $arabic->arIdentify($html);

        for ($i = count($p) - 1; $i >= 0; $i -= 2) {
            $utf8ar = $arabic->utf8Glyphs(substr($html, $p[$i - 1], $p[$i] - $p[$i - 1]));
            $html = substr_replace($html, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
        }

        return $html;
    }
}
