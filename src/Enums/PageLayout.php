<?php

namespace Laravilt\Panel\Enums;

enum PageLayout: string
{
    case Panel = 'panel';
    case Card = 'card';
    case Simple = 'simple';
    case Full = 'full';
    case Settings = 'settings';
}
