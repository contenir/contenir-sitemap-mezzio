<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio;

/**
 * The <changefreq> values the sitemap protocol allows.
 *
 * @see https://www.sitemaps.org/protocol.html#changefreqdef
 *
 * @api
 */
enum ChangeFrequency: string
{
    case Always  = 'always';
    case Hourly  = 'hourly';
    case Daily   = 'daily';
    case Weekly  = 'weekly';
    case Monthly = 'monthly';
    case Yearly  = 'yearly';
    case Never   = 'never';
}
