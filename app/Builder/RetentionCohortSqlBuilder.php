<?php

namespace App\Builder;

/**
 * Registration-cohort retention matrix. Walks the same bucket sequence the period
 * aggregation uses (start of range, stepped by intervalType), numbering each
 * bucket with an idx. Every registered user is assigned to the cohort bucket its
 * registration time falls into; every return login (counter >= 2) is assigned to
 * the bucket its time falls into. The age period of a login is the difference of
 * the two bucket indexes, which keeps the offset correct across the variable
 * length of week/month/year buckets (a plain epoch division would not).
 *
 * Only return logins (counter >= 2, the same definition the DAU metric uses) count
 * as retained: counter 1 is the registration login itself, so period 0 is the
 * cohort baseline (100 %, added by the caller) rather than a measured value, and
 * the SQL only emits period_offset >= 1.
 *
 * stats_users is aliased to `stats` so the shared :projectCondition /
 * :trackingCondition / property / event fragments apply verbatim (the table
 * carries every stats_track, location, device and page_entry column), giving the
 * cohorts the exact same channel attribution as the rest of the stats. The
 * triangular shape and the not-yet-observable cells fall out of the bucket bound:
 * a cohort only has rows for the periods whose bucket still lies inside the range,
 * everything beyond is simply absent (null in the caller, not 0 %).
 *
 * Every bucket is emitted (LEFT JOIN cohort_sizes), not only the ones with
 * registrations, so a bucket without signups still carries its label and a size of
 * 0. That gives the caller one row per bucket in the range, keeping the cohort date
 * axis aligned with the selected range (empty buckets render as 0-user rows).
 */
class RetentionCohortSqlBuilder extends BaseSqlBuilder
{
    public function build(): string
    {
        $nextBucket = $this->getDateInterval('add', 'bucket_start', 1, ':intervalType');

        return '
            WITH RECURSIVE date_sequence AS (
                SELECT '.$this->getDateSelect().' AS bucket_start, 0 AS idx
                UNION ALL
                SELECT '.$nextBucket.", idx + 1
                FROM date_sequence
                WHERE bucket_start < ':endDateStart'
            ),
            buckets AS (
                SELECT bucket_start, idx, ".$nextBucket.' AS bucket_end
                FROM date_sequence
            ),
            cohort_users AS (
                SELECT stats.id AS user_id, stats.project_id, b.idx AS cohort_idx
                FROM stats_users stats
                JOIN buckets b ON stats.time >= b.bucket_start AND stats.time < b.bucket_end
                WHERE :projectCondition
                    AND stats.registered = true
                    :trackingCondition
                    :propertyConditionUser
                    :eventConditionUser
            ),
            cohort_sizes AS (
                SELECT cohort_idx, COUNT(*) AS cohort_size
                FROM cohort_users
                GROUP BY cohort_idx
            ),
            retained AS (
                SELECT cu.cohort_idx, (b.idx - cu.cohort_idx) AS period_offset,
                    COUNT(DISTINCT cu.user_id) AS retained
                FROM cohort_users cu
                JOIN stats_logins sl ON sl.stats_user_id = cu.user_id
                    AND sl.project_id = cu.project_id
                    AND sl.counter >= 2
                JOIN buckets b ON sl.time >= b.bucket_start AND sl.time < b.bucket_end
                WHERE b.idx > cu.cohort_idx
                GROUP BY cu.cohort_idx, (b.idx - cu.cohort_idx)
            )
            SELECT
                b.idx AS cohort_idx,
                '.$this->getDateFormat('b.bucket_start', ':dateFormat').' AS cohort_label,
                COALESCE(cs.cohort_size, 0) AS cohort_size,
                r.period_offset,
                r.retained
            FROM buckets b
            LEFT JOIN cohort_sizes cs ON cs.cohort_idx = b.idx
            LEFT JOIN retained r ON r.cohort_idx = b.idx
            ORDER BY b.idx ASC, r.period_offset ASC
        ';
    }
}
