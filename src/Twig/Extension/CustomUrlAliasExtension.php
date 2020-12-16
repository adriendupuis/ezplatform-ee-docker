<?php

namespace App\Twig\Extension;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\URLAliasService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CustomUrlAliasExtension extends AbstractExtension
{
    /** @var URLAliasService */
    private $urlAliasService;

    /** @var LocationService */
    private $locationService;

    public function __construct(URLAliasService $urlAliasService, LocationService $locationService)
    {
        $this->urlAliasService = $urlAliasService;
        $this->locationService = $locationService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'shortest_url',
                [$this, 'getShortestUrl']
            ),
        ];
    }

    /**
     * @todo: The shortest URL alias path isn't necessarily the most relevant one. Discarding forwarding ones may help to avoir deprecated ones.
     *
     * @param $params
     *
     * @return string|null
     *
     * @throws InvalidArgumentException
     */
    public function getShortestUrl($params)
    {
        $location = null;
        if (is_object($params)) {
            if ($params instanceof Location) {
                $params = ['location' => $params];
            } elseif ($params instanceof Content) {
                $params = ['content' => $params];
            } else {
                throw new InvalidArgumentException('params', 'Invalid object class');
            }
        } elseif (is_numeric($params)) {
            $params['locationId'] = (int) $params;
        } elseif (!is_array($params)) {
            throw new InvalidArgumentException('params', 'Invalid type');
        }

        if (array_key_exists('location', $params)) {
            $location = $params['location'];
        } elseif (array_key_exists('locationId', $params)) {
            $this->locationService->load($params['location']);
        } elseif (array_key_exists('content', $params)) {
            //TODO
        } elseif (array_key_exists('contentId', $params)) {
            //TODO
        } else {
            throw new InvalidArgumentException('params', 'no location, locationId, content nor contentId found in parameters');
        }

        if (!$location instanceof Location) {
            throw new InvalidArgumentException('params', 'invalid location object');
        }

        $aliases = $this->urlAliasService->listLocationAliases($location, true);
        $shortestPath = null;
        foreach ($aliases as $alias) {
            if (is_null($shortestPath) || strlen($alias->path) < strlen($shortestPath)) {
                $shortestPath = $alias->path;
            }
        }

        return $shortestPath;
        //TODO: siteaccess awareness
    }
}
