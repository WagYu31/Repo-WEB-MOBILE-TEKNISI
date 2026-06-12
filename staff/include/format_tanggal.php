<?php
/**
 * Helper function to replace deprecated strftime() in PHP 8.1+
 * Uses IntlDateFormatter instead.
 * 
 * Include this file once, then replace:
 *   strftime('%d %B %Y', $timestamp)  →  formatTanggal('dd MMMM yyyy', $timestamp)
 *   strftime('%d %B %Y')              →  formatTanggal('dd MMMM yyyy')
 *   strftime('%B %Y', $timestamp)     →  formatTanggal('MMMM yyyy', $timestamp)
 *   strftime('%d %b %Y', $timestamp)  →  formatTanggal('dd MMM yyyy', $timestamp)
 *   strftime('%A', $timestamp)        →  formatTanggal('EEEE', $timestamp)
 */

if (!function_exists('formatTanggal')) {
    /**
     * Format tanggal dengan locale Indonesia (pengganti strftime)
     * 
     * @param string $pattern ICU date pattern (e.g. 'dd MMMM yyyy', 'EEEE', 'MMMM yyyy')
     * @param int|string|null $dateInput Unix timestamp, date string, or null for now
     * @return string Formatted date string
     */
    function formatTanggal(string $pattern = 'dd MMMM yyyy', $dateInput = null): string {
        $fmt = new IntlDateFormatter(
            'id_ID',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'Asia/Jakarta',
            IntlDateFormatter::GREGORIAN,
            $pattern
        );
        
        if ($dateInput === null) {
            return $fmt->format(new DateTime());
        }
        
        if (is_int($dateInput)) {
            // Unix timestamp
            return $fmt->format($dateInput);
        }
        
        // Date string
        return $fmt->format(new DateTime($dateInput));
    }
}
