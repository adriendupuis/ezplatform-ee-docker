// Our Backend - Assuming that web server is listening on port 80
// Replace the host to fit your setup
//
// For additional example see:
// https://github.com/ezsystems/ezplatform/blob/master/doc/docker/entrypoint/varnish/parameters.vcl

backend ezplatform {
    .host = "apache";
    .port = "80";
}

// ACL for invalidators IP
//
// Alternative using HTTPCACHE_VARNISH_INVALIDATE_TOKEN : VCL code also allows for token based invalidation, to use it define a
//      shared secret using env variable HTTPCACHE_VARNISH_INVALIDATE_TOKEN and eZ Platform will also use that for configuring this
//      bundle. This is prefered for setups such as platform.sh/eZ Platform Cloud, where circular service dependency is
//      unwanted. If you use this, use a strong cryptological secure hash & make sure to keep the token secret.
// Use ez_purge_acl for invalidation by token.
acl invalidators {
    "apache";
}

// ACL for debuggers IP
acl debuggers {
    "172.27.0.1";
}