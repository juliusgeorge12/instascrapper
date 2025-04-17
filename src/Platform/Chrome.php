<?php

namespace InstaScrapper\Platform;

class Chrome extends BasePlatform
{
    protected $name = 'Google Chrome';

    protected $version = "135";

    protected $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36";

    protected $ua = '"Google Chrome";v="135", "Chromium";v="135", "Not_A Brand";v="8"';
}
