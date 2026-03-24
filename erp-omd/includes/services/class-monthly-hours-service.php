<?php

class ERP_OMD_Monthly_Hours_Service
{
    public function suggested_hours($year_month)
    {
        $date = DateTimeImmutable::createFromFormat('Y-m', $year_month);
        if (! $date) {
            return 0;
        }

        $start = $date->modify('first day of this month');
        $end = $date->modify('last day of this month');
        $working_days = 0;

        for ($cursor = $start; $cursor <= $end; $cursor = $cursor->modify('+1 day')) {
            $weekday = (int) $cursor->format('N');
            if ($weekday <= 5) {
                ++$working_days;
            }
        }

        return $working_days * 8;
    }

    public function suggested_hours_for_date($date)
    {
        $month = gmdate('Y-m', strtotime($date));
        return $this->suggested_hours($month);
    }

    public function calculate_hourly_cost($monthly_salary, $monthly_hours)
    {
        $monthly_salary = (float) $monthly_salary;
        $monthly_hours = (float) $monthly_hours;

        if ($monthly_hours <= 0) {
            return 0.0;
        }

        return round($monthly_salary / $monthly_hours, 2);
    }
}
