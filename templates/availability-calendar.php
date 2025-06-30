<div class="guesty-availability-calendar" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <h3><?php esc_html_e('Availability', 'guesty-api'); ?></h3>

    <div class="calendar-navigation">
        <button class="prev-month">&lt; <?php esc_html_e('Prev', 'guesty-api'); ?></button>
        <h4 class="current-month"><?php echo date_i18n('F Y'); ?></h4>
        <button class="next-month"><?php esc_html_e('Next', 'guesty-api'); ?> &gt;</button>
    </div>

    <div class="calendar-grid">
        <div class="calendar-header">
            <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day): ?>
                <div><?php esc_html_e($day, 'guesty-api'); ?></div>
            <?php endforeach; ?>
        </div>

        <div class="calendar-days">
            <?php
            $first_day = date('N', strtotime(date('Y-m-01')));
            $days_in_month = date('t');

            // Pad start of month
            for ($i = 1; $i < $first_day; $i++) {
                echo '<div class="empty-day"></div>';
            }

            // Display days
            for ($day = 1; $day <= $days_in_month; $day++) {
                $date = date('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
                $is_available = !in_array($date, $availability['booked_dates'] ?? []);
                $price = $availability['pricing'][$date] ?? null;

                $classes = ['day'];
                $classes[] = $is_available ? 'available' : 'booked';

                echo '<div class="' . implode(' ', $classes) . '">';
                echo '<span class="day-number">' . $day . '</span>';

                if ($show_pricing && $price && $is_available) {
                    echo '<span class="day-price">' . esc_html($price) . '</span>';
                }

                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

