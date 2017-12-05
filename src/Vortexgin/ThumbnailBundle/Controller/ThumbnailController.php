<?php

namespace Vortexgin\ThumbnailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;

class ThumbnailController extends Controller
{

    const TYPE_CROP = 'crop';
    const TYPE_CENTER = 'center';

    const PATH = '/tmp/';

    private static $listType = [self::TYPE_CROP, self::TYPE_CENTER];

    /**
     * @Route("/_images/thumbnail", name="images_thumbnail")
     */
    public function imagesThumbnailAction(Request $request)
    {
        $get = $request->query->all();

        $url = null;
        if (array_key_exists('url', $get) && !empty($get['url'])) {
            $url = urldecode($get['url']);
        }

        $width = null;
        if (array_key_exists('width', $get) && !empty($get['width'])) {
            $width = $get['width'];
        }

        $height = null;
        if (array_key_exists('height', $get) && !empty($get['height'])) {
            $height = $get['height'];
        }

        $quality = null;
        if (array_key_exists('quality', $get) && !empty($get['quality'])) {
            $quality = $get['quality'];
        }

        $type = null;
        if (array_key_exists('type', $get) && !empty($get['type']) && in_array(strtolower($get['type']), self::$listType)) {
            $type = $get['type'];
        }

        if (empty($url)) {
            return new Response('Invalid url', Response::HTTP_BAD_REQUEST);
        }

        $date = new \DateTime();
        $date->modify('+604800 seconds');

        $response = new Response();
        $response->headers->set('Content-Type', 'image/jpeg');
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->setSharedMaxAge(604800);
        $response->setExpires($date);

        $filename = md5(serialize($get)).'.jpg';
        if (file_exists(self::PATH.$filename)) {
            $content = file_get_contents(self::PATH.$filename);
            $response->setContent($content);

            return $response;
        }

        $content = file_get_contents($url, false, 
            stream_context_create(
                array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ),
                )
            )
        );

        $imagine  = new Imagine();
        $image = $imagine->load($content);
        $size = array(
            'width' => $image->getSize()->getWidth(), 
            'height' => $image->getSize()->getHeight(), 
        );
        $dimension = array(
            'width' => array(
                'x' => 1, 
                'y' => $size['height']/$size['width'],             
            ), 
            'height' => array(
                'x' => $size['width']/$size['height'], 
                'y' => 1,             
            ), 
        );

        if (!empty($width) && empty($height)) {
            $height = $width * $dimension['width']['y'];
        } elseif (empty($width) && !empty($height)) {
            $width = $height * $dimension['height']['x'];
        } elseif (empty($width) && empty($height)) {
            $width = $size['width'];
            $height = $size['height'];
        }

        if (strtolower($type) == self::TYPE_CROP) {
            $image = $image->thumbnail(new Box($width, $height), ImageInterface::THUMBNAIL_OUTBOUND);
        }else{
            $image = $image->resize(new Box($width, $height));
        }

        $options = array(
            'resolution-units' => ImageInterface::RESOLUTION_PIXELSPERINCH,
            'jpeg_quality' => 60,
        );

        $image->save(self::PATH.$filename);
        $response->setContent($image->show('jpg', $options));

        return $response;
    }

}
