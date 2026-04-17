#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
ARTISAN="$PROJECT_DIR/artisan"

ACTION="${1:-}"
shift || true

EMAIL=""
NAME=""
PASSWORD=""
NEW_EMAIL=""
HARD_DELETE="0"
FORCE="0"
PRIMARY_FROM_ENV="0"

print_usage() {
  cat <<'EOF'
Uso:
  ./scripts/manage-admin.sh <accion> [opciones]

Acciones:
  add       Agrega un admin nuevo o convierte un usuario existente en admin.
  update    Modifica datos de un admin existente.
  delete    Quita privilegios admin (por defecto) o elimina el usuario.
  sync-env  Crea/actualiza el admin principal usando ADMIN_PRIMARY_* del .env.

Opciones generales:
  --email <correo>         Correo del admin objetivo.
  --name <nombre>          Nombre del admin (add/update).
  --password <clave>       Clave nueva (minimo 6 caracteres).
  --new-email <correo>     Nuevo correo para update.
  --hard                   En delete, elimina el usuario de forma permanente.
  --force                  No pedir confirmacion interactiva.
  -h, --help               Muestra esta ayuda.

Ejemplos:
  ./scripts/manage-admin.sh add --email admin@miempresa.com --name "Admin" --password "Secret123!"
  ./scripts/manage-admin.sh update --email admin@miempresa.com --name "Admin Ops"
  ./scripts/manage-admin.sh update --email admin@miempresa.com --new-email ops@miempresa.com
  ./scripts/manage-admin.sh delete --email admin@miempresa.com
  ./scripts/manage-admin.sh delete --email admin@miempresa.com --hard --force
  ./scripts/manage-admin.sh sync-env --force
EOF
}

fail() {
  printf '[ERROR] %s\n' "$1" >&2
  exit 1
}

info() {
  printf '[INFO] %s\n' "$1"
}

success() {
  printf '[OK] %s\n' "$1"
}

require_tools() {
  command -v php >/dev/null 2>&1 || fail "No se encontro php en PATH"
  [ -f "$ARTISAN" ] || fail "No se encontro artisan. Ejecuta el script desde el repo."
}

is_valid_email() {
  local value="$1"
  [[ "$value" =~ ^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$ ]]
}

parse_args() {
  while [ "$#" -gt 0 ]; do
    case "$1" in
      --email)
        EMAIL="${2:-}"
        shift 2
        ;;
      --name)
        NAME="${2:-}"
        shift 2
        ;;
      --password)
        PASSWORD="${2:-}"
        shift 2
        ;;
      --new-email)
        NEW_EMAIL="${2:-}"
        shift 2
        ;;
      --hard)
        HARD_DELETE="1"
        shift
        ;;
      --force)
        FORCE="1"
        shift
        ;;
      -h|--help)
        print_usage
        exit 0
        ;;
      *)
        fail "Opcion no reconocida: $1"
        ;;
    esac
  done
}

ask_if_empty() {
  local label="$1"
  local current="$2"

  if [ -n "$current" ]; then
    printf '%s' "$current"
    return 0
  fi

  read -r -p "$label: " value
  printf '%s' "$value"
}

ask_password_if_empty() {
  local current="$1"

  if [ -n "$current" ]; then
    printf '%s' "$current"
    return 0
  fi

  read -r -s -p "Password: " pass1
  printf '\n'
  read -r -s -p "Confirmar password: " pass2
  printf '\n'

  if [ "$pass1" != "$pass2" ]; then
    fail "Las contrasenas no coinciden"
  fi

  printf '%s' "$pass1"
}

confirm_or_abort() {
  local msg="$1"
  if [ "$FORCE" = "1" ]; then
    return 0
  fi

  read -r -p "$msg [y/N]: " answer
  case "$answer" in
    y|Y|yes|YES)
      return 0
      ;;
    *)
      fail "Operacion cancelada"
      ;;
  esac
}

run_tinker() {
  local snippet="$1"
  ADMIN_EMAIL="$EMAIL" \
  ADMIN_NAME="$NAME" \
  ADMIN_PASSWORD="$PASSWORD" \
  ADMIN_NEW_EMAIL="$NEW_EMAIL" \
    php "$ARTISAN" tinker --execute "$snippet"
}

validate_action() {
  case "$ACTION" in
    add|update|delete|sync-env)
      ;;
    -h|--help|"")
      print_usage
      exit 0
      ;;
    *)
      fail "Accion no valida: $ACTION"
      ;;
  esac
}

do_add() {
  EMAIL="$(ask_if_empty "Email" "$EMAIL")"
  NAME="$(ask_if_empty "Nombre" "$NAME")"
  PASSWORD="$(ask_password_if_empty "$PASSWORD")"

  is_valid_email "$EMAIL" || fail "Email invalido"
  [ "${#PASSWORD}" -ge 6 ] || fail "La password debe tener al menos 6 caracteres"

  info "Creando/actualizando admin: $EMAIL"
  run_tinker '
    $email = trim((string) getenv("ADMIN_EMAIL"));
    $name = trim((string) getenv("ADMIN_NAME"));
    $password = (string) getenv("ADMIN_PASSWORD");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "Email invalido\n");
        exit(2);
    }

    if (strlen($password) < 6) {
        fwrite(STDERR, "Password muy corta\n");
        exit(3);
    }

    $user = \App\Models\User::query()->where("email", $email)->first();

    if ($user) {
        $user->name = $name !== "" ? $name : $user->name;
        $user->password = $password;
        $user->is_admin = true;
        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
        }
        $user->save();
        echo "Admin actualizado: {$user->email}\n";
        exit(0);
    }

    $newUser = \App\Models\User::query()->create([
        "name" => $name !== "" ? $name : "Admin",
        "email" => $email,
        "email_verified_at" => now(),
        "password" => $password,
        "is_admin" => true,
    ]);

    echo "Admin creado: {$newUser->email}\n";
  '
  success "Operacion completada"
}

do_update() {
  EMAIL="$(ask_if_empty "Email actual del admin" "$EMAIL")"
  is_valid_email "$EMAIL" || fail "Email actual invalido"

  if [ -n "$NEW_EMAIL" ]; then
    is_valid_email "$NEW_EMAIL" || fail "Nuevo email invalido"
  fi

  if [ -z "$NAME" ] && [ -z "$PASSWORD" ] && [ -z "$NEW_EMAIL" ]; then
    read -r -p "Nuevo nombre (dejar vacio para no cambiar): " NAME
    read -r -p "Nuevo email (dejar vacio para no cambiar): " NEW_EMAIL
    if [ -n "$NEW_EMAIL" ]; then
      is_valid_email "$NEW_EMAIL" || fail "Nuevo email invalido"
    fi
    read -r -s -p "Nueva password (dejar vacio para no cambiar): " PASSWORD
    printf '\n'
    if [ -n "$PASSWORD" ] && [ "${#PASSWORD}" -lt 6 ]; then
      fail "La password debe tener al menos 6 caracteres"
    fi
  fi

  info "Modificando admin: $EMAIL"
  run_tinker '
    $email = trim((string) getenv("ADMIN_EMAIL"));
    $name = trim((string) getenv("ADMIN_NAME"));
    $password = (string) getenv("ADMIN_PASSWORD");
    $newEmail = trim((string) getenv("ADMIN_NEW_EMAIL"));

    $user = \App\Models\User::query()->where("email", $email)->first();
    if (!$user) {
        fwrite(STDERR, "No existe usuario con ese email\n");
        exit(2);
    }

    if ((bool) $user->is_admin !== true) {
        fwrite(STDERR, "El usuario existe pero no es admin\n");
        exit(3);
    }

    if ($newEmail !== "" && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "Nuevo email invalido\n");
        exit(4);
    }

    if ($newEmail !== "" && $newEmail !== $email) {
        $exists = \App\Models\User::query()->where("email", $newEmail)->exists();
        if ($exists) {
            fwrite(STDERR, "Ya existe un usuario con el nuevo email\n");
            exit(5);
        }
        $user->email = $newEmail;
    }

    if ($name !== "") {
        $user->name = $name;
    }

    if ($password !== "") {
        if (strlen($password) < 6) {
            fwrite(STDERR, "Password muy corta\n");
            exit(6);
        }
        $user->password = $password;
    }

    $user->is_admin = true;
    if (!$user->email_verified_at) {
        $user->email_verified_at = now();
    }

    $user->save();
    echo "Admin actualizado: {$user->email}\n";
  '
  success "Operacion completada"
}

do_delete() {
  EMAIL="$(ask_if_empty "Email del admin" "$EMAIL")"
  is_valid_email "$EMAIL" || fail "Email invalido"

  if [ "$HARD_DELETE" = "1" ]; then
    confirm_or_abort "Esto eliminara el usuario completo ($EMAIL). Continuar?"
    info "Eliminando usuario admin: $EMAIL"
    run_tinker '
      $email = trim((string) getenv("ADMIN_EMAIL"));
      $user = \App\Models\User::query()->where("email", $email)->first();
      if (!$user) {
          fwrite(STDERR, "No existe usuario con ese email\n");
          exit(2);
      }
      if (!(bool) $user->is_admin) {
          fwrite(STDERR, "El usuario no es admin\n");
          exit(3);
      }
      $user->delete();
      echo "Usuario eliminado: {$email}\n";
    '
    success "Operacion completada"
    return
  fi

  confirm_or_abort "Se quitara el rol admin para $EMAIL (sin borrar usuario). Continuar?"
  info "Quitando rol admin: $EMAIL"
  run_tinker '
    $email = trim((string) getenv("ADMIN_EMAIL"));
    $user = \App\Models\User::query()->where("email", $email)->first();
    if (!$user) {
        fwrite(STDERR, "No existe usuario con ese email\n");
        exit(2);
    }
    if (!(bool) $user->is_admin) {
        fwrite(STDERR, "El usuario ya no es admin\n");
        exit(3);
    }
    $user->is_admin = false;
    $user->save();
    echo "Rol admin removido para: {$user->email}\n";
  '
  success "Operacion completada"
}

do_sync_env() {
  PRIMARY_FROM_ENV="1"

  info "Leyendo ADMIN_PRIMARY_NAME, ADMIN_PRIMARY_EMAIL y ADMIN_PRIMARY_PASSWORD desde .env"

  run_tinker '
    $name = trim((string) env("ADMIN_PRIMARY_NAME", ""));
    $email = trim((string) env("ADMIN_PRIMARY_EMAIL", ""));
    $password = (string) env("ADMIN_PRIMARY_PASSWORD", "");

    if ($email === "" || $password === "") {
        fwrite(STDERR, "Faltan ADMIN_PRIMARY_EMAIL o ADMIN_PRIMARY_PASSWORD en .env\n");
        exit(2);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "ADMIN_PRIMARY_EMAIL no es valido\n");
        exit(3);
    }

    if (strlen($password) < 6) {
        fwrite(STDERR, "ADMIN_PRIMARY_PASSWORD debe tener al menos 6 caracteres\n");
        exit(4);
    }

    $user = \App\Models\User::query()->where("email", $email)->first();

    if ($user) {
        $user->name = $name !== "" ? $name : $user->name;
        $user->password = $password;
        $user->is_admin = true;
        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
        }
        $user->save();
        echo "Admin principal actualizado: {$user->email}\n";
        exit(0);
    }

    $newUser = \App\Models\User::query()->create([
        "name" => $name !== "" ? $name : "Admin Principal",
        "email" => $email,
        "email_verified_at" => now(),
        "password" => $password,
        "is_admin" => true,
    ]);

    echo "Admin principal creado: {$newUser->email}\n";
  '

  success "Admin principal sincronizado desde .env"
}

main() {
  require_tools
  validate_action
  parse_args "$@"

  case "$ACTION" in
    add)
      do_add
      ;;
    update)
      do_update
      ;;
    delete)
      do_delete
      ;;
    sync-env)
      do_sync_env
      ;;
  esac
}

main "$@"
