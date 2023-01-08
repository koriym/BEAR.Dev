<?php

namespace BEAR\Dev\Halo;

use BEAR\Dev\CacheLoader;
use BEAR\Dev\DevInvoker;
use BEAR\Dev\TemplatePathInterface;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\Request;
use BEAR\Resource\ResourceObject;
use DateInterval;
use DateTime;
use Ray\Aop\WeavedInterface;
use Ray\Di\Di\Named;
use ReflectionClass;
use ReflectionObject;
use Traversable;
use function BEAR\Dev\gettype;
use function file_put_contents;
use function json_encode;
use function str_contains;
use function str_replace;
use function substr;
use const JSON_PRETTY_PRINT;
use const PHP_EOL;

final class HaloRenderer implements RenderInterface
{
    private const HALO_KEY = 'halo';
    private const HALO_COOKIE_KEY = '_bear_sunday_disable_halo';

    private const NO_CACHE = 'label-default';
    private const WRITE_CACHE = 'label-danger';
    private const READ_CACHE = 'label-success';
    private const BADGE_ARGS = '<span class="badge badge-info">Arguments</span>';
    private const BADGE_CACHE = '<span class="badge badge-info">Cache</span>';
    private const BADGE_INTERCEPTORS = '<span class="badge badge-info">Interceptors</span>';
    private const BADGE_PROFILE = '<span class="badge badge-info">Profile</span>';
    private const ICON_LIFE = '<span class="glyphicon glyphicon-refresh"></span>';
    private const ICON_TIME = '<span class="glyphicon glyphicon-time"></span>';
    private const ICON_NA = '<span class="glyphicon glyphicon-ban-circle"></span>';
    private const DIV_WELL = '<div style="padding:10px;">';

    public function __construct(
        #[Named('original')]
        private RenderInterface $renderer
    ){
    }

    /**
     * {@inheritdoc}
     */
    public function render(ResourceObject $ro)
    {
        if ($this->isDisableHalo()) {
            return $this->renderer->render($ro);
        }

        // resource code editor
        $pageFile =  $ro instanceof WeavedInterface ?
            (new ReflectionClass($ro))->getParentClass()->getFileName() :
            (new ReflectionClass($ro))->getFileName();


        // resource template editor
//        $dir = pathinfo($pageFile, PATHINFO_DIRNAME);
//        $this->templateEngineAdapter->assign('resource', $ro);
//        if (is_array($ro->body) || $ro->body instanceof Traversable) {
//            $this->templateEngineAdapter->assignAll($ro->body);
//        }
        // rendering with original render
        $originalView =  $this->renderer->render($ro);
        $templatePath = $this->renderer instanceof TemplatePathInterface ? $this->renderer->getTemplatePath($ro) : '';
        // add "halo"
//        $templateFile = $this->makeRelativePath($templatePath);
        $haloView = $this->addHalo($originalView, $ro, $templatePath);
        $toolView = $this->addJsDevTools($haloView);
        $ro->view = $toolView;

        return $haloView;
    }

    private function isDisableHalo(): bool
    {
        $disableHalo = (isset($_GET[self::HALO_KEY]) && $_GET[self::HALO_KEY] === '0') || isset($_COOKIE[self::HALO_COOKIE_KEY]);
        if (! empty($_GET[self::HALO_KEY]) && $_GET[self::HALO_KEY] === '1') {
            $disableHalo = false;
            setcookie(self::HALO_COOKIE_KEY, '', time() - 3600);
        }

        if ($disableHalo) {
            setcookie(self::HALO_COOKIE_KEY, '0');
        }

        return $disableHalo;
    }

    /**
     * Return JS install html for dev tool
     *
     * @param string $body
     *
     * @return string
     */
    private function addJsDevTools($body)
    {
        if (strpos($body, '<head>') === false) {
            return $body;
        }
        $bootstrapCss = '<link href="https://koriym.github.io/BEAR.Package/assets/css/bootstrap.bear.css" rel="stylesheet">' . PHP_EOL .
            '<link href="https://koriym.github.io/BEAR.Package/assets/css/bear.dev.css" rel="stylesheet">' . PHP_EOL;
        $bootstrapCss .= strpos($body, 'glyphicons.css') ? '' : '<link href="https://netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-glyphicons.css" rel="stylesheet">' . PHP_EOL;
        $tabJs = strpos($body, '/assets/js/bootstrap-tab.js') ? '' : '<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/js/bootstrap-tab.js"></script>' . PHP_EOL;
        $bootstrapJs = '<link href="https://netdna.bootstrapcdn.com/bootswatch/3.0.0/united/bootstrap.min.css" rel="stylesheet">';
        $toolLoad = <<<EOT
<!-- BEAR.Sunday dev tools -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
{$bootstrapCss}{$tabJs}{$bootstrapJs}
<!-- /BEAR.Sunday dev tools -->
EOT;
        $replacedBody = str_replace('<head>', "<head>\n{$toolLoad}", $body);

        return $replacedBody;
    }

    /**
     * Get relative path from system root.
     *
     * @param string $file
     *
     * @return mixed
     * @return string
     */
    private function makeRelativePath($file)
    {
        return $file;
    }

    /**
     * Return label
     *
     * @param                $body
     * @param ResourceObject $resourceObject
     * @param                $templateFile
     *
     * @return string
     */
    private function addHalo($body, ResourceObject $resourceObject, $templateFile)
    {
        $labelColor = self::READ_CACHE;
//        $cache = isset($resourceObject->headers[CacheLoader::HEADER_CACHE]) ? json_decode($resourceObject->headers[CacheLoader::HEADER_CACHE], true) : false;
//        if ($cache === false) {
//            $labelColor = self::NO_CACHE;
//        } elseif (isset($cache['mode']) && $cache['mode'] === 'W') {
//            $labelColor = self::WRITE_CACHE;
//        } else {
//            $labelColor = self::READ_CACHE;
//        }

        // var
        $result = $this->addResourceMetaInfo($resourceObject, $labelColor, $templateFile, $body);

        return $result;
    }

    /**
     * @param ResourceObject $resourceObject
     * @param                $labelColor
     * @param                $templateFile
     * @param                $body
     *
     * @return string
     */
    private function addResourceMetaInfo(ResourceObject $resourceObject, $labelColor, $templateFile, $body)
    {
        $resourceName = ($resourceObject->uri ? : get_class($resourceObject));
        // code editor
        $ref = new ReflectionObject($resourceObject);
        $codeFile = ($resourceObject instanceof WeavedInterface) ? $ref->getParentClass()->getFileName(): $ref->getFileName();
//        $codeFile = $this->makeRelativePath($codeFile);
        $var = $this->getVar($resourceObject->body);
        $resourceKey = spl_object_hash($resourceObject);
        $escapedBody = preg_replace('/<!-- BEAR\.Sunday dev tool load -->.*BEAR\.Sunday dev tool load -->/', '', $body);

        $resourceBody = preg_replace_callback(
            '/<!-- resource(.*?)resource_tab_end -->/s',
            function ($matches) {
                $uri = substr(explode(' ', $matches[1])[0], 1);
                preg_match('/ <!-- resource_body_start -->(.*?)<!-- resource_body_end -->/s', $matches[1], $resourceBodyMatch);
                return "<!-- resource:$uri -->\n{$resourceBodyMatch[1]}<!-- /resource:$uri -->";
            },
            $escapedBody
        );
        $resourceBodyHtml = highlight_string($resourceBody, true);
        $info = $this->getResourceInfo($resourceObject);
        $rmReturn = function ($str) {
            return $str;
            return str_replace("\n", '', $str);
        };
        $result = <<<EOT
<!-- resource:{$resourceName} -->
<div class="bearsunday">
<div class="toolbar">
    <span class="label {$labelColor}">{$resourceName}</span>
    <a data-toggle="tab" href="#{$resourceKey}_body" class="home"><span class="glyphicon glyphicon-home"
    rel="tooltip" title="Home"></span></a>
    <a data-toggle="tab" href="#{$resourceKey}_var"><span class="glyphicon glyphicon-zoom-in" rel="tooltip"
    title="Status"></span></a>
    <a data-toggle="tab" href="#{$resourceKey}_html"><span class="glyphicon glyphicon-font" rel="tooltip"
    title="View"></span></a>
    <a data-toggle="tab" href="#{$resourceKey}_info"><span class="glyphicon glyphicon-info-sign" rel="tooltip"
    title="Info"></span></a>
    <span class="edit">
        <a href="phpstorm://open?file={$codeFile}"><span class="glyphicon glyphicon-edit"
        rel="tooltip" title="Code ({$codeFile})"></span></a>
        <a target="_blank" href="/dev/edit/index.php?file={$templateFile}"><span class="glyphicon glyphicon-file"
        rel="tooltip" title="Template ({$templateFile})"></span></a>
    </span>
</div>

<div class="tab-content frame">
    <div id="{$resourceKey}_body" class="tab-pane fade active in">
    <!-- resource_body_start -->
EOT;
        $result = $rmReturn($result);
//        $result .= $body;
        $result = str_contains($body, '<body>' ) ? str_replace('<body>', '<body>' . $result, $body) : $result . $body;
        $label = <<<EOT
<!-- resource_body_end -->
<!-- /resource:'{$resourceName}' -->
<!-- resource_tab_start -->
    </div>
    <div id="{$resourceKey}_var" class="tab-pane">
        <div class="tab-wrap">
            <span class="badge badge-info">State</span><br>{$var}
        </div>
    </div>
    <div id="{$resourceKey}_html" class="tab-pane">
        <div class="tab-wrap">
            <span class="badge badge-info">View</span><br>{$resourceBodyHtml}
        </div>
    </div>
    <div id="{$resourceKey}_info" class="tab-pane">
        <div class="tab-wrap">{$info}</div>
    </div>
</div>
</div>
<!-- resource_tab_end -->
EOT;
        $result .= $rmReturn($label);

        return $result;
    }

    /**
     * Return var
     *
     * @param mixed $body
     *
     * @return bool|float|int|mixed|string
     * @return string
     */
    private function getVar($body)
    {
        if (is_scalar($body)) {
            return $body;
        }
        $isTraversable = (is_array($body) || $body instanceof Traversable);
        if (!$isTraversable) {
            return '-';
        }
        array_walk_recursive(
            $body,
            function (&$value) {
                if ($value instanceof Request) {
                    $value = '[' . $value->toUri() . ']';
                }
                if ($value instanceof ResourceObject) {
                    $value = $value->body;
                }
                if (is_object($value)) {
                    /** @var $value object */
                    $value = '(object) ' . get_class($value);
                }
            }
        );

        return '<pre>' . json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</pre>';
    }

    /**
     * Return resource meta info
     *
     * @param ResourceObject $resourceObject
     *
     * @return string
     */
    private function getResourceInfo(ResourceObject $resourceObject)
    {
        $info = '';
//        $info .= $this->getParamsInfo($resourceObject);
        $info .= $this->getInterceptorInfo($resourceObject);
//        $info .= $this->getCacheInfo($resourceObject);
        $info .= $this->getProfileInfo($resourceObject);

        return $info;
    }

    /**
     * Return method invocation arguments info
     *
     * @param ResourceObject $resourceObject
     *
     * @return string
     * @return string
     */
    private function getParamsInfo(ResourceObject $resourceObject)
    {
        $result = self::BADGE_ARGS . self::DIV_WELL;
//        if (isset($resourceObject->headers[DevInvoker::HEADER_PARAMS])) {
//            $params = json_decode($resourceObject->headers[DevInvoker::HEADER_PARAMS], true);
//        } else {
//            $params = [];
//        }

        foreach ($params as $param) {
            if (is_scalar($param)) {
                $type = gettype($param);
            } elseif (is_object($param)) {
                $type = get_class($param);
            } elseif (is_array($param)) {
                $type = 'array';
                $param = print_r($param, true);
            } elseif (is_null($param)) {
                $type = 'null';
            } else {
                $type = 'unknown';
            }
            $param = htmlspecialchars($param, ENT_QUOTES, "UTF-8");
            $paramInfo = "<li>($type) {$param}</li>";
        }
        if ($params === []) {
            $paramInfo = 'void';
        }
        /** @noinspection PhpUndefinedVariableInspection */
        $result .= "<ul>{$paramInfo}</ul>";

        return $result . '</div>';
    }

    /**
     * Return resource meta info
     *
     * @param ResourceObject $resourceObject
     *
     * @return string
     */
    private function getInterceptorInfo(ResourceObject $resourceObject)
    {
        $result = self::BADGE_INTERCEPTORS . self::DIV_WELL;
        if (!isset($resourceObject->headers[DevInvoker::HEADER_INTERCEPTORS])) {
            return $result . 'n/a</div>';
        }
        $result .= '<ul class="unstyled">';
        $interceptors = json_decode($resourceObject->headers[DevInvoker::HEADER_INTERCEPTORS], true);
        unset($resourceObject->headers[DevInvoker::HEADER_INTERCEPTORS]);
        $onGetInterceptors = isset($interceptors['onGet']) ? $interceptors['onGet'] : [];
        foreach ($onGetInterceptors as $interceptor) {
            $interceptorFile = (new ReflectionClass($interceptor))->getFileName();
            $result .= <<<EOT
<li style="height: 26px;"><a target="_blank" href="phpstorm://open?file={$interceptorFile}">{$interceptor}</a></li>
EOT;
        }
        $result .= '</ul></div>';

        return $result;
    }

    /**
     * Return cache info
     *
     * @param ResourceObject $resourceObject
     *
     * @return string
     * @return string
     */
    private function getCacheInfo(ResourceObject $resourceObject)
    {
        $cache = isset($resourceObject->headers[CacheLoader::HEADER_CACHE]) ? json_decode(
            $resourceObject->headers[CacheLoader::HEADER_CACHE],
            true
        ) : false;
        $result = self::BADGE_CACHE . self::DIV_WELL;
        if ($cache === false) {
            return $result . 'n/a</div>';
        }
        $iconLife = self::ICON_LIFE;
        $iconTime = self::ICON_TIME;

        $life = $cache['life'] ? "{$cache['life']} sec" : 'Unlimited';
        if (isset($cache['context']) && $cache['context'] === 'W') {
            $result .= "Write {$iconLife} {$life}";
        } else {
            if ($cache['life'] === false) {
                $time = $cache['date'];
            } else {
                $created = new DateTime($cache['date']);
                $interval = new DateInterval("PT{$cache['life']}S");
                $expire = $created->add($interval);
                $time = $expire->diff(new DateTime('now'))->format('%h hours %i min %s sec left');
            }
            $result .= "Read {$iconLife} {$life} {$iconTime} {$time}";
        }

        return $result . '</div>';
    }

    /**
     * Return resource meta info
     *
     * @param ResourceObject $resourceObject
     *
     * @return string
     */
    private function getProfileInfo(ResourceObject $resourceObject)
    {
        // memory, time
        $result = self::BADGE_PROFILE . self::DIV_WELL;
        if (isset($resourceObject->headers[DevInvoker::HEADER_EXECUTION_TIME])) {
            $time = number_format($resourceObject->headers[DevInvoker::HEADER_EXECUTION_TIME], 3);
        } else {
            $time = 0;
        }
        if (isset($resourceObject->headers[DevInvoker::HEADER_MEMORY_USAGE])) {
            $memory = number_format($resourceObject->headers[DevInvoker::HEADER_MEMORY_USAGE]);
        } else {
            $memory = 0;
        }
        $result .= <<<EOT
<span class="icon-time"></span> {$time} sec <span class="icon-signal"></span> {$memory} bytes
EOT;
        // profile id
        if (isset($resourceObject->headers[DevInvoker::HEADER_PROFILE_ID])) {
            $profileId = $resourceObject->headers[DevInvoker::HEADER_PROFILE_ID];
            $result .= <<<EOT
<span class="icon-random"></span><a href="/xhprof_html/index.php?run={$profileId}&source=resource"> {$profileId}</a>
EOT;
        }
        $result .= '</div>';

        return $result;
    }
}
