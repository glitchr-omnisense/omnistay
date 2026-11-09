<?php

/*
 * The omnistay/* providers the harness installs beside the core: their tests'
 * namespace, and what their tests need besides (require-dev).
 */
return [
    'amadeus' => ['Omnistay\\Amadeus\\Tests\\'],
    'local' => ['Omnistay\\Local\\Tests\\'],
];
