<?php

namespace App\Service;

use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\URLAliasService;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\URLAlias;
use eZ\Publish\Core\FieldType\Checkbox;

class CustomUrlAliasService
{
    /** @var URLAliasService */
    private $urlAliasService;

    /** @var LocationService */
    private $locationService;

    /**
     * Field identifier of the checkbox telling if the content's location must be removed from its childrenh URL path.
     *
     * @todo Get ths from a config file(?)
     *
     * @var string
     */
    private $hiddenFromUrlFieldIdentifier = 'hidden_from_url';

    public function __construct(URLAliasService $urlAliasService, LocationService $locationService)
    {
        $this->urlAliasService = $urlAliasService;
        $this->locationService = $locationService;
    }

    /**
     * Add custom URL aliases to a location.
     *
     * @param string[]|string|null $languageCodes Language code array or string; If omitted, all language codes will be treated
     *
     * @throws InvalidArgumentException
     */
    public function addUrlAliases(Location $location, $languageCodes = null, Location $parentLocation = null): void
    {
        if (is_string($languageCodes)) {
            $languageCodes = [$languageCodes];
        } elseif (is_null($languageCodes)) {
            //$languageCodes = [$location->getContentInfo()->getMainLanguage()];
            $languageCodes = $location->getContent()->getVersionInfo()->languageCodes;
        }
        if (!is_array($languageCodes)) {
            throw new \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException('$languageCode', 'is not an array');
        }
        foreach ($languageCodes as $languageCode) {
            $this->addUrlAliasWithHiddenElements($location, $languageCode, $parentLocation);
        }
    }

    /**
     * Calculate a path where some ancestor are hidden from the path (not truly hidden, just removed from the slug) and add it as an URL alias.
     *
     * @param Location|null $parentLocation If given, this will be used as the parent instead of the one attached to the location; Useful, for example, when moving a location from an old parent to a new one
     */
    private function addUrlAliasWithHiddenElements(Location $location, string $languageCode, Location $parentLocation = null): void
    {
        [$newPath, $locationPathIsModified, $childrenPathIsModified] = array_values($this->getUrlAliasWithHiddenElements($location, $languageCode, $parentLocation));

        dump($location->id, $newPath);

        if ($locationPathIsModified && !empty($newPath) && '/' !== $newPath) {
            $urlAlias = null;
            $urlAliasAlreadyExists = false;
            try {
                $urlAlias = $this->urlAliasService->lookup($newPath);
            } catch (NotFoundException $notFoundException) {
                // The new path is free to use, this is good
            } catch (InvalidArgumentException $invalidArgumentException) {
                //TODO
            }
            if ($urlAlias instanceof URLAlias) {
                if (URLAlias::LOCATION === $urlAlias->type) {
                    if ($urlAlias->destination === $location->id) {
                        if ($urlAlias->forward) {
                            $this->undeprecateUrlAliasWithHiddenElements($location, $languageCode);
                        }
                        $urlAliasAlreadyExists = true;
                    } else {
                        // path already in use by an other location
                        if ($urlAlias->forward) {
                            // This is a redirect, maybe a deprecated URL alias
                            $this->urlAliasService->removeAliases([$urlAlias]);
                            $urlAliasAlreadyExists = false;
                        } else {
                            // This is direct alias
                            //TODO: When can this happens? Is it safe to replace this?
                            $this->urlAliasService->removeAliases([$urlAlias]);
                            $urlAliasAlreadyExists = false;
                        }
                    }
                } else {
                    // URL alias' destination is a string
                    //TODO: It seems a bit dangerous to remove an alias of this type. Case must be carefully studied. A notification could be sent to user.
                    $urlAliasAlreadyExists = true;
                }
            }

            dump($urlAlias, $urlAliasAlreadyExists);

            if (!$urlAliasAlreadyExists) {
                try {
                    $urlAlias = $this->urlAliasService->createUrlAlias(
                        $location,
                        $newPath,
                        $languageCode,
                        false
                    );
                    dump($urlAlias);
                } catch (InvalidArgumentException $invalidArgumentException) {
                    // The path already exists
                    // TODO:
                    // If the path already exists for another object, there is an editorial problem
                    // If the path already exists for another location or language of the same object, there is a developer problem as it should have ben avoided by !in_array($newPath, $pathList)
                } catch (UnauthorizedException $unauthorizedException) {
                    // TODO: In which case could it happens? What to do about it?
                }
            }
        }

        if (!is_null($parentLocation)) {
            // As location moves to a new parent, remove actual URL alias
            $this->deprecateUrlAliasWithHiddenElements($location, $languageCode);
        }
        //TODO: Deprecate previous version path if it's different (like when renaming)?

        if ($childrenPathIsModified && $childCount = $this->locationService->getLocationChildCount($location)) {
            // Recursion
            $children = $this->locationService->loadLocationChildren($location, 0, $childCount);
            foreach ($children as $child) {
                $this->addUrlAliasWithHiddenElements($child, $languageCode);
            }
        }
    }

    /**
     * Delete location's “UrlAliasWithHiddenElements” if it exists.
     *
     * @todo Recursive removing is a difficult task.
     *
     * @deprecated To deprecateUrlAliasWithHiddenElements until it is needed for a new location seems safer and more useful
     */
    private function removeUrlAliasWithHiddenElements(Location $location, string $languageCode, Location $parentLocation = null): void
    {
        $path = $this->getUrlAliasWithHiddenElements($location, $languageCode, $parentLocation)['path'];
        try {
            $urlAlias = $this->urlAliasService->lookup($path);
            $this->urlAliasService->removeAliases([$urlAlias]);
        } catch (NotFoundException $notFoundException) {
            // Nothing to remove; if it doesn't exist, no problem
        } catch (UnauthorizedException $unauthorizedException) {
            //TODO
        } catch (InvalidArgumentException $invalidArgumentException) {
            //TODO
        }
    }

    /**
     * Update the “UrlAliasWithHiddenElements” `forwarding` property.
     *
     * @param $forwarding
     *
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
     */
    private function updateUrlAliasWithHiddenElements(Location $location, string $languageCode, $forwarding)
    {
        $path = $this->getUrlAliasWithHiddenElements($location, $languageCode, null)['path'];
        $urlAlias = null;
        try {
            $urlAlias = $this->urlAliasService->lookup($path);
        } catch (NotFoundException $notFoundException) {
            // Nothing to update; if it doesn't exist, no problem
        }
        if (!is_null($urlAlias) && $urlAlias->forward !== $forwarding) {
            // URLAliasService seems to haven't an update function, so, remove and recreate
            $this->urlAliasService->removeAliases([$urlAlias]);
            $this->urlAliasService->createUrlAlias(
                $location,
                $path,
                $languageCode,
                $forwarding
            );
        }
    }

    /**
     * Switch the “UrlAliasWithHiddenElements” `forwarding` property from false (Direct) to true (Redirect).
     */
    private function deprecateUrlAliasWithHiddenElements(Location $location, string $languageCode)
    {
        $this->updateUrlAliasWithHiddenElements($location, $languageCode, true);
    }

    /**
     * Switch the “UrlAliasWithHiddenElements” `forwarding` property from true (Redirect) to false (Direct).
     *
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    private function undeprecateUrlAliasWithHiddenElements(Location $location, string $languageCode)
    {
        $this->updateUrlAliasWithHiddenElements($location, $languageCode, false);
    }

    /**
     * Calculate the “UrlAliasWithHiddenElements” itself.
     *
     * @param Location|null $parentLocation If given, this will be used as the parent instead of the location property
     */
    private function getUrlAliasWithHiddenElements(Location $location, string $languageCode, Location $parentLocation = null): array
    {
        if (is_null($parentLocation)) {
            $ancestors = $this->locationService->loadLocationList($location->path);
        } else {
            $ancestors = $this->locationService->loadLocationList($parentLocation->path);
            $ancestors[] = $location;
        }
        $path = '';
        $locationPathIsModified = false;
        $childrenPathIsModified = false;

        /** @var Location $ancestor */
        foreach ($ancestors as $ancestor) {
            /** @var Checkbox\Value|null $fieldValue */
            $hiddenFromUrlFieldValue = $ancestor->getContent()->getFieldValue($this->hiddenFromUrlFieldIdentifier, $languageCode);
            if (null === $hiddenFromUrlFieldValue) {
                // The content type doesn't have this field
                $ancestorPathElementIsHidden = false;
            } elseif (!$hiddenFromUrlFieldValue instanceof Checkbox\Value) {
                // The content type has this field but not of the right field type
                //TODO: Throw/log an error
                $ancestorPathElementIsHidden = false;
            } else {
                $ancestorPathElementIsHidden = $hiddenFromUrlFieldValue->bool;
            }

            // If an ancestor is hidden from path, children of current location need custom URL aliases too
            $childrenPathIsModified |= $ancestorPathElementIsHidden;

            if ($ancestorPathElementIsHidden && $ancestor->id !== $location->id) {
                // Remove hidden ancestor (but not current location itself)
                $locationPathIsModified = true;
            } else {
                // Add ancestor or current location
                $ancestorUrlAliases = $this->urlAliasService->listLocationAliases($ancestor, false, $languageCode);
                if (!empty($ancestorUrlAliases)) {
                    $ancestorPathElements = explode('/', trim($ancestorUrlAliases[0]->path, '/'));
                    if (count($ancestorPathElements)) {
                        $ancestorPathElement = $ancestorPathElements[count($ancestorPathElements) - 1];
                        if (!empty($ancestorPathElement)) {
                            $path .= "/$ancestorPathElement";
                        }
                    }
                }
            }
        }

        return [
            'path' => $path,
            'locationPathIsModified' => $locationPathIsModified,
            'childrenPathIsModified' => $childrenPathIsModified,
        ];
    }

    /**
     * @return string[]
     */
    private function getExistingPathList(Location $location, string $languageCode): array
    {
        $pathList = [];
        foreach ([false, true] as $custom) {
            foreach ($this->urlAliasService->listLocationAliases($location, $custom, $languageCode) as $alias) {
                $pathList[] = $alias->path;
            }
        }

        return $pathList;
    }
}
