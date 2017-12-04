<?php

namespace Vortexgin\ThumbnailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ThumbnailController extends AbstractController
{
    /**
     * @Route("/_images/thumbnail", name="images_thumbnail")
     */
    public function imagesThumbnailAction(Request $request)
    {
        
        return new Response(
            '<html><body>Lucky number: '.$number.'</body></html>'
        );
    }

}
