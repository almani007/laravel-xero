<?php
namespace Almani\Xero\Console;
use Illuminate\Console\Command;
use Almani\Xero\Services\XeroService;
class SyncXeroInvoices extends Command
{
    protected $signature = 'xero:sync-invoices {user_id}';
    protected $description = 'Sync invoices from Xero for a user';
    protected $xeroService;
    public function __construct(XeroService $xeroService){ parent::__construct(); $this->xeroService=$xeroService; }
    public function handle(){ /* Implementation from previous chunks */ }
}