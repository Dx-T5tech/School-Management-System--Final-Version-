<?php

declare(strict_types=1);

$summary = $dashboard['summary'];
$summaryFormatValue = static function (float|int $value, string $format): string {
    return match ($format) {
        'amount' => number_format((float) $value, 2),
        'percent' => number_format((float) $value, 1) . '%',
        default => number_format((float) $value),
    };
};
?>
<section class="dashboard-summary" aria-labelledby="dashboard-summary-heading">
    <div class="dashboard-summary-heading">
        <div>
            <p class="section-overline">Reports and trends</p>
            <h2 id="dashboard-summary-heading"><?= dashboardEscape($summary['heading']) ?></h2>
        </div>
        <span class="data-note">
            <span class="data-note-dot" aria-hidden="true"></span>
            <?= dashboardEscape($summary['periodLabel']) ?>
        </span>
    </div>

    <?php if ($summary['cards'] !== []): ?>
        <div class="dashboard-summary-cards">
            <?php foreach ($summary['cards'] as $card): ?>
                <article class="dashboard-summary-card">
                    <?php if (!empty($card['href'])): ?>
                        <a href="<?= dashboardEscape($card['href']) ?>">
                            <span class="dashboard-summary-card-label"><?= dashboardEscape($card['label']) ?></span>
                            <strong><?= dashboardEscape($card['value']) ?></strong>
                        </a>
                    <?php else: ?>
                        <span class="dashboard-summary-card-label"><?= dashboardEscape($card['label']) ?></span>
                        <strong><?= dashboardEscape($card['value']) ?></strong>
                    <?php endif; ?>
                    <p><?= dashboardEscape($card['note']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($summary['charts'] !== []): ?>
        <div class="dashboard-summary-charts">
            <?php foreach ($summary['charts'] as $chart): ?>
                <?php
                $chartMax = 0.0;
                foreach ($chart['series'] as $series) {
                    foreach ($series['values'] as $value) {
                        $chartMax = max($chartMax, (float) $value);
                    }
                }
                $chartMax = max(1.0, $chartMax);
                ?>
                <figure class="dashboard-summary-chart">
                    <figcaption>
                        <h3><?= dashboardEscape($chart['title']) ?></h3>
                        <p><?= dashboardEscape($chart['description']) ?></p>
                    </figcaption>

                    <?php if ($chart['labels'] === [] || $chart['series'] === []): ?>
                        <p class="dashboard-summary-empty"><?= dashboardEscape($chart['emptyMessage']) ?></p>
                    <?php else: ?>
                        <?php if (count($chart['series']) > 1): ?>
                            <ul class="dashboard-chart-legend" aria-label="Chart series">
                                <?php foreach ($chart['series'] as $series): ?>
                                    <li>
                                        <span class="dashboard-chart-swatch dashboard-chart-swatch--<?= dashboardEscape($series['tone']) ?>" aria-hidden="true"></span>
                                        <?= dashboardEscape($series['label']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <ol class="dashboard-chart-rows">
                            <?php foreach ($chart['labels'] as $labelIndex => $label): ?>
                                <li class="dashboard-chart-category">
                                    <h4><?= dashboardEscape($label) ?></h4>
                                    <ul>
                                        <?php foreach ($chart['series'] as $series): ?>
                                            <?php
                                            $value = (float) ($series['values'][$labelIndex] ?? 0);
                                            $barWidth = min(100, max(0, $value / $chartMax * 100));
                                            ?>
                                            <li class="dashboard-chart-value">
                                                <?php if (count($chart['series']) === 1): ?>
                                                    <span class="dashboard-chart-series-name sr-only"><?= dashboardEscape($series['label']) ?></span>
                                                <?php else: ?>
                                                    <span class="dashboard-chart-series-name"><?= dashboardEscape($series['label']) ?></span>
                                                <?php endif; ?>
                                                <span class="dashboard-chart-track" aria-hidden="true">
                                                    <span class="dashboard-chart-bar dashboard-chart-bar--<?= dashboardEscape($series['tone']) ?>" style="width: <?= dashboardEscape(number_format($barWidth, 2, '.', '')) ?>%"></span>
                                                </span>
                                                <strong><?= dashboardEscape($summaryFormatValue($value, $chart['format'])) ?></strong>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($summary['lists'])): ?>
        <div class="dashboard-summary-lists">
            <?php foreach ($summary['lists'] as $list): ?>
                <section class="dashboard-summary-list" aria-label="<?= dashboardEscape($list['title']) ?>">
                    <div class="dashboard-summary-list-heading">
                        <div>
                            <h3><?= dashboardEscape($list['title']) ?></h3>
                            <p><?= dashboardEscape($list['description']) ?></p>
                        </div>
                        <?php if (!empty($list['href'])): ?>
                            <a href="<?= dashboardEscape($list['href']) ?>"><?= dashboardEscape($list['linkLabel']) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php if ($list['items'] === []): ?>
                        <p class="dashboard-summary-empty"><?= dashboardEscape($list['emptyMessage']) ?></p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($list['items'] as $item): ?>
                                <li>
                                    <div>
                                        <strong><?= dashboardEscape($item['title']) ?></strong>
                                        <span><?= dashboardEscape($item['detail']) ?></span>
                                    </div>
                                    <?php if (!empty($item['status'])): ?>
                                        <span class="dashboard-summary-list-status"><?= dashboardEscape($item['status']) ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
