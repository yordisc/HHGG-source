#!/bin/bash

################################################################################
# Script para crear una nueva certificación/curso
# Uso: ./scripts/create-certification.sh
################################################################################

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables globales
CERT_SLUG=""
CERT_NAME=""
CERT_DESC=""
QUESTIONS_REQUIRED=30
PASS_SCORE=66.67
COOLDOWN_DAYS=30
RESULT_MODE="binary_threshold"
QUESTIONS_ARRAY=()
QUESTION_PROMPTS=()
QUESTION_OPTION_1=()
QUESTION_OPTION_2=()
QUESTION_OPTION_3=()
QUESTION_OPTION_4=()
QUESTION_CORRECT_OPTIONS=()
CURRENT_STEP=1
NON_INTERACTIVE=0
PDF_VIEW="pdf.certificate"
HOME_ORDER=100
ACTIVE=1
SETTINGS_JSON=""
AUTO_CREATE_TEST_QUESTIONS=0

################################################################################
# Funciones Auxiliares
################################################################################

print_header() {
    echo -e "\n${BLUE}===============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}===============================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_usage() {
    cat <<EOF
Uso:
  ./scripts/create-certification.sh

Modo por argumentos:
  ./scripts/create-certification.sh \
    --slug <slug> \
    --name <nombre> \
    [--description <texto>] \
    [--questions-required <1-255>] \
    [--pass-score <0-100>] \
    [--cooldown-days <0-365>] \
    [--result-mode <binary_threshold|custom|generic>] \
    [--pdf-view <vista_blade>] \
    [--home-order <0-9999>] \
    [--settings-json '<json>'] \
    [--active|--inactive] \
    [--with-test-questions <1-20>] \
    [--yes]

Ejemplo:
  ./scripts/create-certification.sh --slug marketing-2026 --name "Marketing 2026" --questions-required 20 --pass-score 70 --with-test-questions 5 --yes
EOF
}

parse_args() {
    if [ $# -eq 0 ]; then
        return
    fi

    NON_INTERACTIVE=1

    while [ $# -gt 0 ]; do
        case "$1" in
            --slug)
                CERT_SLUG="$2"
                shift 2
                ;;
            --name)
                CERT_NAME="$2"
                shift 2
                ;;
            --description)
                CERT_DESC="$2"
                shift 2
                ;;
            --questions-required)
                QUESTIONS_REQUIRED="$2"
                shift 2
                ;;
            --pass-score)
                PASS_SCORE="$2"
                shift 2
                ;;
            --cooldown-days)
                COOLDOWN_DAYS="$2"
                shift 2
                ;;
            --result-mode)
                RESULT_MODE="$2"
                shift 2
                ;;
            --pdf-view)
                PDF_VIEW="$2"
                shift 2
                ;;
            --home-order)
                HOME_ORDER="$2"
                shift 2
                ;;
            --settings-json)
                SETTINGS_JSON="$2"
                shift 2
                ;;
            --active)
                ACTIVE=1
                shift
                ;;
            --inactive)
                ACTIVE=0
                shift
                ;;
            --with-test-questions)
                AUTO_CREATE_TEST_QUESTIONS="$2"
                shift 2
                ;;
            --yes)
                AUTO_CONFIRM=1
                shift
                ;;
            --help|-h)
                print_usage
                exit 0
                ;;
            *)
                print_error "Argumento no reconocido: $1"
                print_usage
                exit 1
                ;;
        esac
    done
}

validate_non_interactive_inputs() {
    if [ -z "$CERT_SLUG" ] || [ -z "$CERT_NAME" ]; then
        print_error "En modo por argumentos, --slug y --name son obligatorios"
        exit 1
    fi

    if ! is_valid_slug "$CERT_SLUG"; then
        print_error "Slug inválido"
        exit 1
    fi

    if ! is_slug_unique "$CERT_SLUG"; then
        print_error "El slug ya existe"
        exit 1
    fi

    if ! [[ "$QUESTIONS_REQUIRED" =~ ^[0-9]+$ ]] || [ "$QUESTIONS_REQUIRED" -lt 1 ] || [ "$QUESTIONS_REQUIRED" -gt 255 ]; then
        print_error "--questions-required debe estar entre 1 y 255"
        exit 1
    fi

    if ! [[ "$PASS_SCORE" =~ ^[0-9]+(\.[0-9]{1,2})?$ ]] || ! awk "BEGIN { exit !($PASS_SCORE >= 0 && $PASS_SCORE <= 100) }"; then
        print_error "--pass-score debe estar entre 0 y 100"
        exit 1
    fi

    if ! [[ "$COOLDOWN_DAYS" =~ ^[0-9]+$ ]] || [ "$COOLDOWN_DAYS" -lt 0 ] || [ "$COOLDOWN_DAYS" -gt 365 ]; then
        print_error "--cooldown-days debe estar entre 0 y 365"
        exit 1
    fi

    if [[ "$RESULT_MODE" != "binary_threshold" && "$RESULT_MODE" != "custom" && "$RESULT_MODE" != "generic" ]]; then
        print_error "--result-mode debe ser binary_threshold, custom o generic"
        exit 1
    fi

    if [ -n "$SETTINGS_JSON" ]; then
        if ! echo "$SETTINGS_JSON" | php -r 'json_decode(stream_get_contents(STDIN), true); exit(json_last_error() === JSON_ERROR_NONE ? 0 : 1);'; then
            print_error "--settings-json no es JSON válido"
            exit 1
        fi
    fi

    if ! [[ "$AUTO_CREATE_TEST_QUESTIONS" =~ ^[0-9]+$ ]] || [ "$AUTO_CREATE_TEST_QUESTIONS" -lt 0 ] || [ "$AUTO_CREATE_TEST_QUESTIONS" -gt 20 ]; then
        print_error "--with-test-questions debe estar entre 0 y 20"
        exit 1
    fi
}

# Validar que estamos en la raíz del proyecto
check_project_root() {
    if [ ! -f "artisan" ]; then
        print_error "Este script debe ejecutarse desde la raíz del proyecto"
        exit 1
    fi
    print_success "Proyecto verificado"
}

# Validar entorno Laravel
check_laravel_env() {
    if [ ! -f ".env" ]; then
        print_error "No se encontró archivo .env"
        exit 1
    fi
    print_success "Entorno Laravel configurado"
}

# Validar conexión a BD
check_database_connection() {
    if ! php artisan tinker --execute "echo 'OK';" > /dev/null 2>&1; then
        print_error "No se puede conectar a la base de datos"
        echo "Verifica las variables en .env"
        exit 1
    fi
    print_success "Conexión a base de datos OK"
}

# Validar que slug sea único
is_slug_unique() {
    local slug="$1"
    local exists=$(php artisan tinker --execute "
        \$count = \App\Models\Certification::where('slug', '$slug')->count();
        echo \$count;
    " 2>/dev/null)
    
    [ "$exists" = "0" ]
}

# Validar formato de slug
is_valid_slug() {
    local slug="$1"
    if [[ ! "$slug" =~ ^[a-z0-9_-]+$ ]]; then
        return 1
    fi
    if [ ${#slug} -lt 3 ] || [ ${#slug} -gt 60 ]; then
        return 1
    fi
    return 0
}

# Pedir y validar slug
input_slug() {
    print_info "El slug es el identificador único de la certificación (ej: marketing, python, design)"
    while true; do
        read -p "Ingresa el slug: " CERT_SLUG
        
        if ! is_valid_slug "$CERT_SLUG"; then
            print_error "Slug inválido. Solo letras minúsculas, números, guiones y guiones bajos (3-60 caracteres)"
            continue
        fi
        
        if ! is_slug_unique "$CERT_SLUG"; then
            print_error "Este slug ya existe"
            continue
        fi
        
        print_success "Slug validado: $CERT_SLUG"
        break
    done
}

# Pedir y validar nombre
input_name() {
    print_info "El nombre será visible en la interfaz pública"
    while true; do
        read -p "Ingresa el nombre de la certificación: " CERT_NAME
        
        if [ -z "$CERT_NAME" ] || [ ${#CERT_NAME} -lt 3 ]; then
            print_error "El nombre debe tener al menos 3 caracteres"
            continue
        fi
        
        if [ ${#CERT_NAME} -gt 120 ]; then
            print_error "El nombre no debe exceder 120 caracteres"
            continue
        fi
        
        print_success "Nombre validado: $CERT_NAME"
        break
    done
}

# Pedir descripción
input_description() {
    read -p "Ingresa la descripción (Enter para dejar vacío): " CERT_DESC
    if [ -n "$CERT_DESC" ]; then
        print_success "Descripción ingresada: ${CERT_DESC:0:50}..."
    else
        CERT_DESC=""
        print_info "Descripción vacía"
    fi
}

# Pedir cantidad de preguntas
input_questions_count() {
    while true; do
        read -p "Cantidad de preguntas requeridas [default: 30]: " input
        QUESTIONS_REQUIRED=${input:-30}
        
        if ! [[ "$QUESTIONS_REQUIRED" =~ ^[0-9]+$ ]] || [ $QUESTIONS_REQUIRED -lt 1 ] || [ $QUESTIONS_REQUIRED -gt 255 ]; then
            print_error "Debe ser un número entre 1 y 255"
            continue
        fi
        
        print_success "Preguntas requeridas: $QUESTIONS_REQUIRED"
        break
    done
}

# Pedir porcentaje de aprobación
input_pass_score() {
    while true; do
        read -p "Porcentaje de aprobación [default: 66.67]: " input
        PASS_SCORE=${input:-66.67}
        
        if ! [[ "$PASS_SCORE" =~ ^[0-9]+(\.[0-9]{1,2})?$ ]] || ! awk "BEGIN { exit !($PASS_SCORE >= 0 && $PASS_SCORE <= 100) }"; then
            print_error "Debe ser un número entre 0 y 100"
            continue
        fi
        
        print_success "Porcentaje de aprobación: $PASS_SCORE%"
        break
    done
}

# Pedir cooldown en días
input_cooldown() {
    while true; do
        read -p "Cooldown entre intentos (días) [default: 30]: " input
        COOLDOWN_DAYS=${input:-30}
        
        if ! [[ "$COOLDOWN_DAYS" =~ ^[0-9]+$ ]] || [ $COOLDOWN_DAYS -lt 0 ] || [ $COOLDOWN_DAYS -gt 365 ]; then
            print_error "Debe ser un número entre 0 y 365"
            continue
        fi
        
        print_success "Cooldown: $COOLDOWN_DAYS días"
        break
    done
}

# Agregar preguntas interactivamente
input_questions() {
    print_info "Agregarás $QUESTIONS_REQUIRED preguntas. Cada pregunta tiene 4 opciones."
    echo "Opciones estándar: 1=Siempre, 2=A veces, 3=Raramente, 4=Nunca"
    read -p "¿Usar opciones estándar? (s/n) [default: s]: " use_standard
    use_standard=${use_standard:-s}
    
    local q_num=1
    while [ $q_num -le $QUESTIONS_REQUIRED ]; do
        echo -e "\n${BLUE}Pregunta $q_num de $QUESTIONS_REQUIRED${NC}"
        
        read -p "Ingresa el texto de la pregunta: " prompt
        if [ -z "$prompt" ]; then
            print_warning "Pregunta vacía, intenta de nuevo"
            continue
        fi
        
        if [ "$use_standard" = "n" ]; then
            echo "Ingresa las 4 opciones:"
            read -p "  Opción 1: " opt1
            read -p "  Opción 2: " opt2
            read -p "  Opción 3: " opt3
            read -p "  Opción 4: " opt4
        else
            opt1="Siempre"
            opt2="A veces"
            opt3="Raramente"
            opt4="Nunca"
        fi
        
        while true; do
            read -p "Número de opción correcta (1-4): " correct_opt
            if [[ "$correct_opt" =~ ^[1-4]$ ]]; then
                break
            fi
            print_error "Debe ser un número entre 1 y 4"
        done
        
        QUESTION_PROMPTS+=("$prompt")
        QUESTION_OPTION_1+=("$opt1")
        QUESTION_OPTION_2+=("$opt2")
        QUESTION_OPTION_3+=("$opt3")
        QUESTION_OPTION_4+=("$opt4")
        QUESTION_CORRECT_OPTIONS+=("$correct_opt")
        QUESTIONS_ARRAY+=("$prompt")
        print_success "Pregunta $q_num agregada"
        ((q_num++))
        
        # Opción para saltar si se alcanza el mínimo
        if [ $q_num -gt 5 ]; then
            read -p "¿Agregar más preguntas? (s/n) [default: s]: " continue_input
            continue_input=${continue_input:-s}
            if [ "$continue_input" = "n" ]; then
                QUESTIONS_REQUIRED=$((q_num - 1))
                break
            fi
        fi
    done
    
    print_success "Total de preguntas ingresadas: ${#QUESTIONS_ARRAY[@]}"
}

# Mostrar resumen
show_summary() {
    print_header "RESUMEN DE LA CERTIFICACIÓN"

    local desc_preview="$CERT_DESC"
    if [ ${#CERT_DESC} -gt 50 ]; then
        desc_preview="${CERT_DESC:0:50}..."
    fi
    
    echo -e "${BLUE}Configuración básica:${NC}"
    echo "  Slug:                 $CERT_SLUG"
    echo "  Nombre:               $CERT_NAME"
    echo "  Descripción:          $desc_preview"
    echo ""
    echo -e "${BLUE}Configuración de examen:${NC}"
    echo "  Preguntas requeridas: $QUESTIONS_REQUIRED"
    echo "  % de aprobación:      $PASS_SCORE%"
    echo "  Cooldown:             $COOLDOWN_DAYS días"
    echo "  Modo de resultado:    $RESULT_MODE"
    echo "  Vista PDF:            $PDF_VIEW"
    echo "  Orden home:           $HOME_ORDER"
    echo "  Activa:               $ACTIVE"
    echo "  Settings JSON:        ${SETTINGS_JSON:-<vacío>}"
    echo ""
    echo -e "${BLUE}Preguntas:${NC}"
    echo "  Total:                ${#QUESTIONS_ARRAY[@]}"
    if [ "$AUTO_CREATE_TEST_QUESTIONS" -gt 0 ]; then
        echo "  Test automáticas:     $AUTO_CREATE_TEST_QUESTIONS"
    fi
    echo ""
}

# Crear la certificación en BD
create_certification() {
    print_header "Creando certificación..."
    
    # Crear certificación usando artisan tinker
    CERT_SLUG_ENV="$CERT_SLUG" \
    CERT_NAME_ENV="$CERT_NAME" \
    CERT_DESC_ENV="$CERT_DESC" \
    CERT_QUESTIONS_REQUIRED_ENV="$QUESTIONS_REQUIRED" \
    CERT_PASS_SCORE_ENV="$PASS_SCORE" \
    CERT_COOLDOWN_DAYS_ENV="$COOLDOWN_DAYS" \
    CERT_RESULT_MODE_ENV="$RESULT_MODE" \
    CERT_PDF_VIEW_ENV="$PDF_VIEW" \
    CERT_HOME_ORDER_ENV="$HOME_ORDER" \
    CERT_ACTIVE_ENV="$ACTIVE" \
    CERT_SETTINGS_JSON_ENV="$SETTINGS_JSON" \
    php artisan tinker --execute "
    \$settings = getenv('CERT_SETTINGS_JSON_ENV');
    \$settings = \$settings !== '' ? json_decode(\$settings, true) : null;
    \$certification = \App\Models\Certification::create([
        'slug' => getenv('CERT_SLUG_ENV'),
        'name' => getenv('CERT_NAME_ENV'),
        'description' => getenv('CERT_DESC_ENV'),
        'questions_required' => (int) getenv('CERT_QUESTIONS_REQUIRED_ENV'),
        'pass_score_percentage' => (float) getenv('CERT_PASS_SCORE_ENV'),
        'cooldown_days' => (int) getenv('CERT_COOLDOWN_DAYS_ENV'),
        'result_mode' => getenv('CERT_RESULT_MODE_ENV'),
        'pdf_view' => getenv('CERT_PDF_VIEW_ENV'),
        'active' => (bool) getenv('CERT_ACTIVE_ENV'),
        'home_order' => (int) getenv('CERT_HOME_ORDER_ENV'),
        'settings' => \$settings,
    ]);
    
    echo 'CERT_CREATED:' . \$certification->id;
    " > /tmp/cert_result.txt 2>&1
    
    # Extraer ID
    local cert_id=$(grep "CERT_CREATED:" /tmp/cert_result.txt | cut -d':' -f2)
    
    if [ -z "$cert_id" ]; then
        print_error "Error al crear la certificación"
        cat /tmp/cert_result.txt
        exit 1
    fi
    
    print_success "Certificación creada con ID: $cert_id"

    if [ "$AUTO_CREATE_TEST_QUESTIONS" -gt 0 ]; then
        print_info "Creando $AUTO_CREATE_TEST_QUESTIONS preguntas de prueba..."
        CERT_ID_ENV="$cert_id" CERT_QTY_ENV="$AUTO_CREATE_TEST_QUESTIONS" php artisan tinker --execute "
        \$count = (int) getenv('CERT_QTY_ENV');
        for (\$i = 1; \$i <= \$count; \$i++) {
            \$correct = random_int(1, 4);
            \$opts = [
                1 => 'Opcion A ' . \$i,
                2 => 'Opcion B ' . \$i,
                3 => 'Opcion C ' . \$i,
                4 => 'Opcion D ' . \$i,
            ];
            \$opts[\$correct] = 'Respuesta correcta ' . \$i;

            \App\Models\Question::create([
                'certification_id' => (int) getenv('CERT_ID_ENV'),
                'prompt' => 'Pregunta de prueba ' . \$i,
                'option_1' => \$opts[1],
                'option_2' => \$opts[2],
                'option_3' => \$opts[3],
                'option_4' => \$opts[4],
                'correct_option' => \$correct,
                'active' => true,
                'is_test_question' => true,
            ]);
        }
        " > /dev/null 2>&1
        print_success "Preguntas de prueba creadas: $AUTO_CREATE_TEST_QUESTIONS"
    fi
    
    # Crear preguntas
    if [ ${#QUESTIONS_ARRAY[@]} -gt 0 ]; then
        print_info "Creando ${#QUESTIONS_ARRAY[@]} preguntas..."
        
        local q_num=1
        for i in "${!QUESTION_PROMPTS[@]}"; do
            local prompt="${QUESTION_PROMPTS[$i]}"
            local opt1="${QUESTION_OPTION_1[$i]}"
            local opt2="${QUESTION_OPTION_2[$i]}"
            local opt3="${QUESTION_OPTION_3[$i]}"
            local opt4="${QUESTION_OPTION_4[$i]}"
            local correct="${QUESTION_CORRECT_OPTIONS[$i]}"
            
            QUESTION_PROMPT_ENV="$prompt" \
            QUESTION_OPT1_ENV="$opt1" \
            QUESTION_OPT2_ENV="$opt2" \
            QUESTION_OPT3_ENV="$opt3" \
            QUESTION_OPT4_ENV="$opt4" \
            QUESTION_CORRECT_ENV="$correct" \
            php artisan tinker --execute "
            \App\Models\Question::create([
                'certification_id' => $cert_id,
                'prompt' => getenv('QUESTION_PROMPT_ENV'),
                'option_1' => getenv('QUESTION_OPT1_ENV'),
                'option_2' => getenv('QUESTION_OPT2_ENV'),
                'option_3' => getenv('QUESTION_OPT3_ENV'),
                'option_4' => getenv('QUESTION_OPT4_ENV'),
                'correct_option' => (int) getenv('QUESTION_CORRECT_ENV'),
                'active' => true,
            ]);
            " > /dev/null 2>&1
            
            echo -ne "\r  Pregunta $q_num de ${#QUESTIONS_ARRAY[@]}..."
            ((q_num++))
        done
        echo ""
        print_success "${#QUESTIONS_ARRAY[@]} preguntas creadas"
    fi
    
    print_success "Certificación lista para usar"
}

# Menú de confirmación
confirm_and_create() {
    while true; do
        read -p "¿Crear esta certificación? (s/n): " confirm
        
        case "$confirm" in
            s|S)
                create_certification
                break
                ;;
            n|N)
                print_warning "Creación cancelada"
                exit 0
                ;;
            *)
                print_error "Por favor ingresa 's' o 'n'"
                ;;
        esac
    done
}

# Menú de edición
edit_menu() {
    while true; do
        echo ""
        echo -e "${BLUE}¿Qué deseas editar?${NC}"
        echo "1) Nombre y descripción"
        echo "2) Configuración de examen"
        echo "3) Preguntas"
        echo "4) Ver resumen y confirmar"
        echo "5) Cancelar"
        
        read -p "Selecciona una opción (1-5): " edit_choice
        
        case "$edit_choice" in
            1)
                input_name
                input_description
                ;;
            2)
                input_questions_count
                input_pass_score
                input_cooldown
                ;;
            3)
                QUESTIONS_ARRAY=()
                input_questions
                ;;
            4)
                show_summary
                confirm_and_create
                show_final_message
                break
                ;;
            5)
                print_warning "Creación cancelada"
                exit 0
                ;;
            *)
                print_error "Opción inválida"
                ;;
        esac
    done
}

# Mensaje final
show_final_message() {
    print_header "¡ÉXITO!"
    echo -e "${GREEN}Tu certificación '$CERT_NAME' ha sido creada exitosamente.${NC}\n"
    echo "Próximos pasos:"
    echo "1. Accede al panel admin: /admin/certifications"
    echo "2. Verifica la nueva certificación: $CERT_NAME"
    echo "3. Edita si necesitas ajustar preguntas o configuración"
    echo ""
    echo "La certificación está marcada como activa y visible en el home."
    echo "Slug: $CERT_SLUG"
}

################################################################################
# FLUJO PRINCIPAL
################################################################################

main() {
    parse_args "$@"

    clear
    print_header "CREAR NUEVA CERTIFICACIÓN/CURSO"
    
    print_info "Este asistente te guiará paso a paso para crear una nueva certificación."
    print_info "Presiona Ctrl+C en cualquier momento para cancelar."
    echo ""
    
    # Validaciones iniciales
    check_project_root
    check_laravel_env
    check_database_connection
    echo ""
    
    if [ "$NON_INTERACTIVE" -eq 1 ]; then
        validate_non_interactive_inputs
        show_summary

        if [ "${AUTO_CONFIRM:-0}" -eq 1 ]; then
            create_certification
            show_final_message
            return
        fi

        confirm_and_create
        show_final_message
        return
    fi

    # Recolectar datos (modo interactivo)
    input_slug
    input_name
    input_description
    input_questions_count
    input_pass_score
    input_cooldown
    
    # Agregar preguntas si lo desea
    echo ""
    read -p "¿Deseas agregar preguntas ahora? (s/n) [default: s]: " add_questions
    add_questions=${add_questions:-s}
    
    if [ "$add_questions" = "s" ]; then
        input_questions
    else
        print_info "Puedes agregar preguntas después desde el panel admin"
    fi
    
    echo ""
    show_summary
    
    # Menú de edición o confirmar
    edit_menu
}

# Ejecutar
main "$@"
