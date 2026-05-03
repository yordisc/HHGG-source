https://effective-space-telegram-95g59v5wxx9hpjg4-8000.app.github.dev/admin/questions


Internal Server Error
ErrorException
Array to string conversion
GET effective-space-telegram-95g59v5wxx9hpjg4-8000.app.github.dev
PHP 8.4.11 — Laravel 11.51.0
12 vendor frames collapsed
15 vendor frames collapsed
1 vendor frame collapsed
1 vendor frame collapsed
43 vendor frames collapsed
1 vendor frame collapsed
resources/views/admin/questions/index.blade.php :221

                </div>
            </div>
 
            <!-- Pagination -->
            <div class="mt-6">
                {{ $questions->links() }}
            </div>
        </div>
    </section>
@endsection

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

https://effective-space-telegram-95g59v5wxx9hpjg4-8000.app.github.dev/admin

x-request-id

bdf7635c99462dceb6c39d61b2977293

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

XSRF-TOKEN=eyJpdiI6IkFpR0d6RUxMMEtlZXBMNWhlSDFSc3c9PSIsInZhbHVlIjoiN1d4QmF0eURlM3IrVFc5Y3E2NnZ5bGdNNFVwTGphdVAxaDlxSklBYmdwTDRDVUliSWt4OHVsUEdJVW9vTWVQVytScGoyamZJTG41dGF4Sy9vbWZ3MDlGalBGdHFsZGhITmFqdUZjeTFtSEdjSTl4THQxOHlMenRZUU04N1ZTL2IiLCJtYWMiOiIyYWNhNDc5Y2U1NmMzODM3YmU1ZWQ2ZTE3MjlhNDNkZWYzMTZiYjE1M2U0ODYyOTUxNThjYzM4ZWYxZWQzMGJiIiwidGFnIjoiIn0%3D; instituto_de_certificaciones_dudosas_session=eyJpdiI6IkRpVGFwZmNzY1NsTXFVZEJuaFZkUVE9PSIsInZhbHVlIjoiOWx2TVd2d2x2UjljbEh4MVkxOGlCUXIzbXNWMXRycFNudWhVQjNLeU9sc0E4QkRETFV5Q1R0VzNJZTBtUUtEL1hUNUUrVWRhNmt5N1hHYlVhZVA3dnVaWUo1YXpJS2RxVHYwS3RYMmhCcXZVT2t0b3FwU3BUZTB2N1BOMDRuQzQiLCJtYWMiOiJkZjQ1NmE1MTRkZTJiOWRhNzAwNjNlYjY4NjYzZWIwOTMxZmEyZWNlN2IwZThhNGY0ZmI1MmI2ZDY5MzJmMmIyIiwidGFnIjoiIn0%3D

x-forwarded-proto

https

x-forwarded-host

effective-space-telegram-95g59v5wxx9hpjg4-8000.app.github.dev

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

mysql (0.96 ms)

select * from `sessions` where `id` = 'wDk2TdVfi5KsQ8Hh2YBNJQJUFZFMb72tMMK1F20k' limit 1

mysql (0.32 ms)

select * from `users` where `id` = 1 limit 1

mysql (0.41 ms)

select `name`, `slug` from `certifications` where `active` = 1 order by `home_order` asc, `name` asc

mysql (0.29 ms)

select count(*) as aggregate from `questions`

mysql (0.39 ms)

select * from `questions` order by `id` desc limit 20 offset 0

mysql (0.32 ms)

select * from `certifications` where `certifications`.`id` in (2)

resources/views/admin/questions/index.blade.php :221

                </div>
            </div>
 
            <!-- Pagination -->
            <div class="mt-6">
                {{ $questions->links() }}
            </div>
        </div>
    </section>
@endsection
