<?php
namespace App\Service;
use Silex\Application;

class Nug
{

    /**
     * Silex app
     * @var Application
     */
    protected $app;

    /**
     * The Zimbra community server admin class for Domains
     * @var \Zimbra\ZCS\Admin\Domain
     */
    protected $zimbraDomainAdmin;

    /**
     * The Zimbra community server admin class for Cos'es
     * @var \Zimbra\ZCS\Admin\Cos
     */
    protected $zimbraCosAdmin;

    /**
     * The Zimbra community server admin class for Accounts
     * @var \Zimbra\ZCS\Admin\Account
     */
    protected $zimbraAccountAdmin;

    /**
     * Initializes properties
     * @param $app Appliction
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Gets an array with all Nug domains
     * @return array
     */
    public function getDomainList()
    {
        $domains = $this->_getZimbraDomainAdmin()->getDomains();

        $domainList = array();
        foreach($domains as $domain){
            $preparedDomain = $this->_prepareDomain($domain);
            $domainList[] = $preparedDomain;
        }
        return $domainList;
    }

    /**
     * Gets a single Nug domain
     * @param string $domain_id
     * @return array
     */
    public function getDomain($domain_id)
    {
        $domain = $this->_getZimbraDomainAdmin()->getDomain($domain_id);
        return  $this->_prepareDomain($domain);
    }

    /**
     * Creates a Domain in the Nug webservice
     * @param \Zimbra\ZCS\Entity\Domain $domain The data to be saved, should be a json decoded object received as payload from a POST request
     * @return array
     */
    public function createDomain(\Zimbra\ZCS\Entity\Domain $domain)
    {
        $domain->setValidator($this->app['validator']);
        $domain->validate();

        // Create a new one in the webservice
        $newDomain = $this->_getZimbraDomainAdmin()->createDomain($domain);

        return  $this->_prepareDomain($newDomain);
    }

    /**
     * Updates a Domain in the Nug webservice
     * @param \Zimbra\ZCS\Entity\Domain $domain The data to be saved, should be a json decoded object received as payload from a POST request
     * @return array
     */
    public function updateDomain(\Zimbra\ZCS\Entity\Domain $domain)
    {
        $domain->setValidator($this->app['validator']);
        $domain->validate();

        // Create a new one in the webservice
        $newDomain = $this->_getZimbraDomainAdmin()->updateDomain($domain);

        return  $this->_prepareDomain($newDomain);
    }

    /**
     * Deletes a Domain from the Nug webservice
     * @param string $domain_id
     * @return bool
     */
    public function deleteDomain($domain_id)
    {
        $result = $this->_getZimbraDomainAdmin()->deleteDomain($domain_id);
        return $result;
    }

    /**
     * Gets an array with all Nug Classes of Service
     * @return array
     */
    public function getCosList()
    {
        $cosses = $this->_getZimbraCosAdmin()->getCosList();

        $cosList = array();
        foreach($cosses as $cos){
            $preparedCos = $this->_prepareCos($cos);
            $cosList[] = $preparedCos;
        }
        return $cosList;
    }

    /**
     * Gets a single Nug Class of Service
     * @param string $cos_id
     * @return \Zimbra\ZCS\Entity\Co
     */
    public function getCos($cos_id)
    {
        $cos = $this->_getZimbraCosAdmin()->getCos($cos_id);
        return  $this->_prepareCos($cos);
    }

    /**
     * Gets all accounts from the webservice that belong to a given domain
     * @param $domain_id
     * @return array
     */
    public function getAccountListByDomain($domain_id)
    {
        $domain = $this->_getZimbraDomainAdmin()->getDomain($domain_id);
        $accounts = $this->_getZimbraAccountAdmin()->getAccountListByDomain($domain->getName());

        $accountList = array();
        foreach($accounts as $account){
            $preparedAccount = $this->_prepareAccount($account);
            $accountList[] = $preparedAccount;
        }

        return $accountList;
    }

    /**
     * Gets a single Nug account
     * @param string $account_id
     * @return array
     */
    public function getAccount($account_id)
    {
        $account = $this->_getZimbraAccountAdmin()->getAccount($account_id);
        return  $this->_prepareAccount($account);
    }

    /**
     * Creates a new account in the Nug webservice
     * @param \Zimbra\ZCS\Entity\Account $account
     * @return array
     */
    public function createAccount(\Zimbra\ZCS\Entity\Account $account)
    {
        $account->setValidator($this->app['validator']);
        $account->validate();

        // Create a new one in the webservice
        $newAccount = $this->_getZimbraAccountAdmin()->createAccount($account);
        return  $this->_prepareAccount($newAccount);
    }

    /**
     * Deletes an Account from the Nug webservice
     * @param string $account_id
     * @return bool
     */
    public function deleteAccount($account_id)
    {
        $result = $this->_getZimbraAccountAdmin()->deleteAccount($account_id);
        return $result;
    }

    /**
     * Gets the Zimbra Domain admin from the DI container
     * @return \Zimbra\ZCS\Admin\Domain
     */
    protected function _getZimbraDomainAdmin()
    {
        if (!$this->zimbraDomainAdmin){
            $this->zimbraDomainAdmin = $this->app['zimbra_admin_domain'];
        }
        return $this->zimbraDomainAdmin;
    }

    /**
     * Gets the Zimbra Cos admin from the DI container
     * @return \Zimbra\ZCS\Admin\Cos
     */
    protected function _getZimbraCosAdmin()
    {
        if (!$this->zimbraCosAdmin){
            $this->zimbraCosAdmin = $this->app['zimbra_admin_cos'];
        }
        return $this->zimbraCosAdmin;
    }

    /**
     * Gets the Zimbra Account admin from the DI container
     * @return \Zimbra\ZCS\Admin\Account
     */
    protected function _getZimbraAccountAdmin()
    {
        if (!$this->zimbraAccountAdmin){
            $this->zimbraAccountAdmin = $this->app['zimbra_admin_account'];
        }
        return $this->zimbraAccountAdmin;
    }

    /**
     * Transforms the response object from the Zimbra soap service into a
     * usable array with only the parameters we need
     * @param \Zimbra\ZCS\Entity\Domain $domain
     * @return array
     */
    private function _prepareDomain(\Zimbra\ZCS\Entity\Domain $domain)
    {
        $result = $domain->toArray();
        $result['subresources'] = array(
            'detail'   => '/nug/domain/'.$domain->getId() . '/',
            'account_list' => '/nug/domain/'.$domain->getId() . '/account/'
        );

        if($domain->getDefaultCosId()){
            $result['subresources']['default_cos'] = '/nug/cos/' . $domain->getDefaultCosId();
        }

        return $result;
    }

    /**
     * Transforms the response object from the Zimbra soap service into a
     * usable array with only the parameters we need
     * @param \Zimbra\ZCS\Entity\Cos $cos
     * @return array
     */
    private function _prepareCos(\Zimbra\ZCS\Entity\Cos $cos)
    {
        $result = $cos->toArray();
        return $result;
    }

    /**
     * Transforms the response object from the Zimbra soap service into a
     * usable array with only the parameters we need
     * @param \Zimbra\ZCS\Entity\Account $account
     * @return array
     */
    private function _prepareAccount(\Zimbra\ZCS\Entity\Account $account)
    {
        $result = $account->toArray();
        return $result;
    }

}