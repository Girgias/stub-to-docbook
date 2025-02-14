<?php

function xmlify_labels(string $label): string
{
    return strtolower(str_replace('_', '-', $label));
}
