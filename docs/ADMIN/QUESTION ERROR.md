https://vigilant-space-adventure-5r7rq6r5799fpppj-8000.app.github.dev/admin/questions


Internal Server Error
ErrorException
Array to string conversion
GET vigilant-space-adventure-5r7rq6r5799fpppj-8000.app.github.dev
PHP 8.4.11 — Laravel 11.51.0


12 vendor frames collapsed
resources/views/admin/questions/index.blade.php :319

                </div>
            </div>
 
            <!-- Pagination -->
            <div class="mt-6">
                {{ $questions->render() }}
            </div>
        </div>
    </section>
@endsection


15 vendor frames collapsed
app/Http/Middleware/EnsureAdminAuthenticated.php :18

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = Auth::user();
 
        if ($user && (bool) $user->is_admin) {
            return $next($request);
        }
 
        return redirect()->route('admin.login')
            ->with('status', 'Ingresa con una cuenta de administrador para continuar.');
    }
}

1 vendor frames collapsed
app/Http/Middleware/SetLocale.php :30

            session(['locale' => $locale]);
        }
 
        App::setLocale($locale);
 
        return $next($request);
    }
}

1 vendor frames collapsed
app/Http/Middleware/SecureHeaders.php :14

class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
 
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
 
        $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net";
        $styleSrc = "'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net";
        $connectSrc = "'self'";
 
43 vendor frames collapsed 
public/index.php :17

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';
 
// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
1 vendor frame collapsed 

Request
GET /admin/questions
Headers

accept

text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8

host

localhost:8000

user-agent

Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0

accept-encoding

gzip, deflate, br, zstd

accept-language

es,en;q=0.9

referer

https://vigilant-space-adventure-5r7rq6r5799fpppj-8000.app.github.dev/admin

x-request-id

5f294e81a7ed89d203cc31ac2c2c4331

x-real-ip

190.120.253.174

x-forwarded-port

443

x-forwarded-scheme

https

x-original-uri

/admin/questions

x-scheme

https

sec-fetch-dest

document

sec-fetch-mode

navigate

sec-fetch-site

same-origin

sec-fetch-user

?1

priority

u=0, i

cookie

XSRF-TOKEN=eyJpdiI6ImxSZ1JDUjcrSERkTndvbWhUWWRvMFE9PSIsInZhbHVlIjoiVXB3UUQ2TnpxeHMxN0dKSDlhTTR4REtBZGpiNVp5ckNLLzE4eXArVHU5a000RDhXQXJrVnd6MFE0ZXFPZ0czMFhjalFkL09uTFUvOWhDcCs1ZzBpeDNmSlQwNFdhdDVhenZrM0lFMVA5Y3JnNnd3RXMxWTUyT1dpbENhSEJOMHIiLCJtYWMiOiIxZGIyMTkxZmJkMjZjMTQ2MzNmMTg5NTcyMzU0ZTNmYzUzYjQ4NTkwMTVmNGMwNGE3OWE1ZjM5OTJlODc1YjMyIiwidGFnIjoiIn0%3D; instituto_de_certificaciones_dudosas_session=eyJpdiI6Ikl4NVVzUkxVTjBPRVdFUEhDWE81NFE9PSIsInZhbHVlIjoicngzL2w5bVQwTGpnUFF4SElVOTlub1JSaTJOQkJzWVZBcm9rQ1lKMEtabVR5TmlOUkxVbnQvWVdPMjAzQjdrUU1mRnJmYnROcDhUa25IbWN1NUhVVUFDL1RsSXU1VmcxYnkwYUZJWnJuaktydURxTTZCbFZIOXhRSG9kZzd5dlciLCJtYWMiOiI2YjcyMjgzZDgwNWRmNTcyMGVlY2ViZWI4ZDY4NTJhNjBmZjBjZjIzNDIyMjk0OTgxZTNjNGQxZjdiMjU4N2I5IiwidGFnIjoiIn0%3D

x-forwarded-proto

https

x-forwarded-host

vigilant-space-adventure-5r7rq6r5799fpppj-8000.app.github.dev

x-forwarded-for

190.120.253.174

proxy-connection

Keep-Alive

Body

No body data

Application
Routing

controller

App\Http\Controllers\Admin\QuestionAdminController@index

route name

admin.questions.index

middleware

web, admin.auth

Database Queries

mysql (1.17 ms)

select * from `sessions` where `id` = 'eK5SJgl2ZQnWYXcY2XC8GII32M8akzukddW1FO6a' limit 1

mysql (0.66 ms)

select * from `users` where `id` = 1 limit 1

mysql (0.74 ms)

select `name`, `slug` from `certifications` where `active` = 1 order by `home_order` asc, `name` asc

mysql (0.47 ms)

select count(*) as aggregate from `questions`

mysql (0.58 ms)

select * from `questions` order by `id` desc limit 20 offset 0

mysql (1.18 ms)

select * from `certifications` where `certifications`.`id` in (2)

