<?php

namespace Jacq\Jaxon;

class EditLitTaxaServer extends \Jaxon\CallableClass
{
    /**
     * jaxon-function react on a change of the source
     *
     * @param array $formData form-values
     * @return \Jaxon\Response\Response
     */
    public function setSource($formData)
    {
        global $response;

        if ($formData['source'] == 'literature') {
            $this->response->assign("ajax_sourcePers", "style.display", 'none');
            $this->response->assign("lbl_et_al", "style.display", 'none');
            $this->response->assign("et_al", "style.display", 'none');
            $this->response->assign("ajax_sourceLit", "style.display", '');
        } else {
            $this->response->assign("ajax_sourcePers", "style.display", '');
            $this->response->assign("lbl_et_al", "style.display", '');
            $this->response->assign("et_al", "style.display", '');
            $this->response->assign("ajax_sourceLit", "style.display", 'none');
        }

        return $this->response;
    }
}
