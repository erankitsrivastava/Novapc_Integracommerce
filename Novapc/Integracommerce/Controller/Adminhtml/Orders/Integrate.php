<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Orders;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Novapc\Integracommerce\Helper\Data as HelperData;
use Novapc\Integracommerce\Helper\IntegrationData;
use Novapc\Integracommerce\Helper\OrderData;
use Novapc\Integracommerce\Model\QueueFactory;

class Integrate extends AbstractOrders
{
    /**
     * @var QueueFactory
     */
    protected $modelQueueFactory;

    public function __construct(Context $context, 
        QueueFactory $modelQueueFactory)
    {
        $this->modelQueueFactory = $modelQueueFactory;

        parent::__construct($context);
    }

    protected function integrateAction() 
    {
        /*CARREGA O MODEL DE CONTROLE DE REQUISICOES DE PEDIDOS*/
        $orderModel = $this->modelQueueFactory->create()->load('Order', 'integra_model');
        /*VERIFICA A QUANTIDADE DE REQUISICOES*/
        $message = IntegrationData::checkRequest($orderModel, 'get');

        if (isset($message)) {
            /*SE FOR RETORNADO UMA MENSAGEM DE ERRO BLOQUEIA O METODO E RETORNA A MENSAGEM AO USUARIO*/
            ObjectManager::getInstance()->get('Magento\Framework\Model\Session')->addError(__($message));
            $orderModel->setAvailable(0);
            $orderModel->save();
            $this->_redirect('*/*/');
        } else {
            /*INICIANDO GET DE PEDIDOS*/
            $requested = HelperData::getOrders();

            if (empty($requested['Orders'])) {
                /*SE NAO FOR RETORNADO PEDIDOS RETORNA A MENSAGEM AO USUARIO*/
                ObjectManager::getInstance()->get('Magento\Framework\Model\Session')->addSuccess(__('Não existe nenhum pedido em Aprovado no momento.'));
                $this->_redirect('*/*/');
            }

            /*INCIA PROCESSO DE CRIACAO DE PEDIDOS*/
            OrderData::startOrders($requested, $orderModel);

            ObjectManager::getInstance()->get('Magento\Framework\Model\Session')->addSuccess(__('Sincronização Completa.'));
            $this->_redirect('*/*/');
        }
    }   
}
