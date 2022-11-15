<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views\Components;

use Miklcct\RailOpenTimetableData\Models\Service;
use Miklcct\RailOpenTimetableData\Models\ServiceProperty;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;
use function Miklcct\ThinPhpApp\Escaper\html;

class ServiceInformation extends PhpTemplate {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly Service $service
        , protected readonly ServiceProperty $serviceProperty
    ) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../../resource/templates/service_information.phtml';
    }

    protected function showRsidWithPortionDescription(string $rsid) : string {
        $main = substr($rsid, 0, 6);
        $suffix = (int)substr($rsid, 6, 2);
        if ($suffix === 0) {
            return '<span class="train_number">' . html($main) . '</span>';
        }
        $portions = [];
        for ($i = 0; $i < 8; ++$i) {
            if ($suffix & 1 << $i) {
                $portions[] = $i + 1;
            }
        }
        return '<span class="train_number">' . html($main) . '</span>' . ' (Portion ' . implode(' & ', $portions) . ')';
    }
}