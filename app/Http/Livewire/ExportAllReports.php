<?php

namespace App\Http\Livewire;

use App\Events\ProcessReport;
use App\Events\ProcessReports;
use App\Traits\ApiSite24x7;
use App\Traits\GenerarReportesSite24x7;
use App\Traits\ReestructurarDatosAPISite24x7;
use Livewire\Component;
use Jenssegers\Date\Date;

class ExportAllReports extends Component
{
    use ApiSite24x7, ReestructurarDatosAPISite24x7, GenerarReportesSite24x7;

    public $period = 7;
    public $last_month;
    public $totalMonitors;
    public $percentage = 0;
    public $completed_reports = 0;
    public $percentage_customers = 0;
    public $completed_customers = 0;
    public $period_report;

    public function mount()
    {
        Date::setLocale('es');
        $this->last_month = ucfirst(Date::now()->subMonth()->format('F'));
    }

    public function render()
    {
        $site24x7Url = env('SITE_24X7_API');
        $refresh_token = $this->getRefreshToken();
        $customers_its = $this->getCustomers($site24x7Url, $refresh_token);
        return view('livewire.export-all-reports', compact('customers_its'));
    }

    public function startProcess()
    {
        $site24x7Url = env('SITE_24X7_API');
        $refresh_token = $this->getRefreshToken();

        $customers_its = $this->getCustomers($site24x7Url, $refresh_token);
        foreach ($customers_its as $customer_it) {
            $customer_id = $customer_it['user_id'];
            $customer = $customer_it['name'];
            $zaaid = $customer_it['zaaid'];
            $monitors = $this->getMonitors($site24x7Url, $zaaid, $refresh_token);
            $this->totalMonitors = count($monitors);
            $this->completed_reports = 0;
            $this->percentage = 0;

            foreach ($monitors as $monitor) {
                $this->processSite24x7Monitors($monitor, $site24x7Url, $refresh_token, $zaaid, $customer, $this->last_month);
                $this->completed_reports++;
                $this->percentage = $this->getPercentage($this->completed_reports, $this->totalMonitors);
                event(new ProcessReport($this->totalMonitors, $this->percentage, $this->completed_reports, $zaaid, $customer));
            }
            $this->completed_customers++;
            $this->percentage_customers = $this->getPercentage($this->completed_customers, count($customers_its));
            event(new ProcessReports(count($customers_its), $this->percentage_customers, $this->completed_customers, $customer_id));
        }
    }
}
