#!/bin/bash

################################################################################
# Script para eliminar una certificación/curso o todas las certificaciones
# Uso: ./scripts/delete-certification.sh
################################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

MODE=""
TARGET=""
CONFIRMATION=""

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

check_project_root() {
    if [ ! -f "artisan" ]; then
        print_error "Este script debe ejecutarse desde la raíz del proyecto"
        exit 1
    fi
    print_success "Proyecto verificado"
}

check_laravel_env() {
    if [ ! -f ".env" ]; then
        print_error "No se encontró archivo .env"
        exit 1
    fi
    print_success "Entorno Laravel configurado"
}

check_database_connection() {
    if ! php artisan tinker --execute "echo 'OK';" > /dev/null 2>&1; then
        print_error "No se puede conectar a la base de datos"
        echo "Verifica las variables en .env"
        exit 1
    fi
    print_success "Conexión a base de datos OK"
}

show_certifications() {
    print_header "CERTIFICACIONES DISPONIBLES"

    php artisan tinker --execute '
    $certifications = \App\Models\Certification::query()
        ->orderBy("id")
        ->get(["id", "slug", "name", "active"]);

    if ($certifications->isEmpty()) {
        echo "No hay certificaciones registradas.\n";
        return;
    }

    foreach ($certifications as $certification) {
        $status = $certification->active ? "activa" : "inactiva";
        echo sprintf("#%d | %s | %s | %s\n", $certification->id, $certification->slug, $certification->name, $status);
    }
    '
}

input_mode() {
    while true; do
        echo ""
        echo "¿Qué deseas eliminar?"
        echo "1) Una certificación específica"
        echo "2) Todas las certificaciones"
        echo "3) Cancelar"
        read -p "Selecciona una opción (1-3): " MODE

        case "$MODE" in
            1)
                MODE="single"
                break
                ;;
            2)
                MODE="all"
                break
                ;;
            3)
                print_warning "Eliminación cancelada"
                exit 0
                ;;
            *)
                print_error "Opción inválida"
                ;;
        esac
    done
}

input_target() {
    if [ "$MODE" = "single" ]; then
        show_certifications
        echo ""
        read -p "Ingresa el slug o ID de la certificación a eliminar: " TARGET

        if [ -z "$TARGET" ]; then
            print_error "Debes indicar un slug o ID"
            exit 1
        fi

        print_info "Se eliminará la certificación: $TARGET"
    fi
}

confirm_deletion() {
    echo ""
    if [ "$MODE" = "single" ]; then
        read -p "Escribe ELIMINAR para confirmar el borrado de una certificación: " CONFIRMATION
        if [ "$CONFIRMATION" != "ELIMINAR" ]; then
            print_warning "Confirmación inválida. Cancelando."
            exit 0
        fi
    else
        read -p "Escribe ELIMINAR TODO para borrar todas las certificaciones: " CONFIRMATION
        if [ "$CONFIRMATION" != "ELIMINAR TODO" ]; then
            print_warning "Confirmación inválida. Cancelando."
            exit 0
        fi
    fi
}

preview_single() {
    local target_value="$1"

    CERT_DELETE_TARGET="$target_value" php artisan tinker --execute '
    use App\Models\Certification;

    $target = getenv("CERT_DELETE_TARGET");
    $certification = Certification::query()
        ->where("slug", $target)
        ->orWhere("id", (int) $target)
        ->withCount(["questions", "certificates", "versions", "statistics"])
        ->first();

    if (!$certification) {
        echo "NOT_FOUND\n";
        return;
    }

    echo "ID: {$certification->id}\n";
    echo "Slug: {$certification->slug}\n";
    echo "Nombre: {$certification->name}\n";
    echo "Preguntas: {$certification->questions_count}\n";
    echo "Certificados: {$certification->certificates_count}\n";
    echo "Versiones: {$certification->versions_count}\n";
    echo "Estadísticas: {$certification->statistics_count}\n";
    ' 
}

delete_single() {
    print_header "ELIMINANDO CERTIFICACIÓN"

    local preview_output
    preview_output=$(preview_single "$TARGET")

    if echo "$preview_output" | grep -q '^NOT_FOUND$'; then
        print_error "No se encontró la certificación '$TARGET'"
        exit 1
    fi

    echo "$preview_output"
    echo ""

    CERT_DELETE_TARGET="$TARGET" php artisan tinker --execute '
    use App\Models\AuditLog;
    use App\Models\Certification;
    use App\Support\CertificateImageStorageService;

    $target = getenv("CERT_DELETE_TARGET");
    $certification = Certification::query()
        ->where("slug", $target)
        ->orWhere("id", (int) $target)
        ->with(["questions", "certificates", "certificateTemplate", "versions", "statistics"])
        ->firstOrFail();

    $storageService = app(CertificateImageStorageService::class);

    foreach ($certification->certificates as $certificate) {
        $storageService->delete($certificate->certificate_image_path);
    }

    $questionIds = $certification->questions->pluck("id")->all();
    $certificateIds = $certification->certificates->pluck("id")->all();
    $versionIds = $certification->versions->pluck("id")->all();
    $statisticsIds = $certification->statistics->pluck("id")->all();
    $templateId = $certification->certificateTemplate?->id;

    AuditLog::query()
        ->where(function ($query) use ($certification, $questionIds, $certificateIds, $versionIds, $statisticsIds, $templateId): void {
            $query->where(function ($subQuery) use ($certification): void {
                $subQuery->where("entity", "Certification")
                    ->where("entity_id", $certification->id);
            })->orWhere(function ($subQuery) use ($questionIds): void {
                $subQuery->where("entity", "Question")
                    ->whereIn("entity_id", $questionIds);
            })->orWhere(function ($subQuery) use ($certificateIds): void {
                $subQuery->where("entity", "Certificate")
                    ->whereIn("entity_id", $certificateIds);
            })->orWhere(function ($subQuery) use ($versionIds): void {
                $subQuery->where("entity", "CertificationVersion")
                    ->whereIn("entity_id", $versionIds);
            })->orWhere(function ($subQuery) use ($statisticsIds): void {
                $subQuery->where("entity", "CertificationStatistic")
                    ->whereIn("entity_id", $statisticsIds);
            })->orWhere(function ($subQuery) use ($templateId): void {
                if ($templateId === null) {
                    $subQuery->whereRaw("1 = 0");
                    return;
                }

                $subQuery->where("entity", "CertificateTemplate")
                    ->where("entity_id", $templateId);
            });
        })
        ->delete();

    $certification->delete();

    echo "DELETED_CERTIFICATION:{$certification->id}:{$certification->slug}\n";
    echo "CASCADE_QUESTIONS:" . count($questionIds) . "\n";
    echo "CASCADE_CERTIFICATES:" . count($certificateIds) . "\n";
    echo "CASCADE_VERSIONS:" . count($versionIds) . "\n";
    echo "CASCADE_STATISTICS:" . count($statisticsIds) . "\n";
    if ($templateId !== null) {
        echo "CASCADE_TEMPLATE:1\n";
    }
    ' >/tmp/delete_cert_result.txt 2>&1

    print_success "Certificación eliminada correctamente"
    cat /tmp/delete_cert_result.txt
}

delete_all() {
    print_header "ELIMINANDO TODAS LAS CERTIFICACIONES"

    local total
    total=$(php artisan tinker --execute 'echo \App\Models\Certification::count();' 2>/dev/null)

    if [ -z "$total" ] || [ "$total" -eq 0 ]; then
        print_warning "No hay certificaciones para eliminar"
        exit 0
    fi

    print_warning "Se eliminarán $total certificaciones y sus registros relacionados"
    echo ""

    php artisan tinker --execute '
    use App\Models\AuditLog;
    use App\Models\Certification;
    use App\Support\CertificateImageStorageService;

    $storageService = app(CertificateImageStorageService::class);
    $certifications = Certification::query()
        ->with(["questions", "certificates", "certificateTemplate", "versions", "statistics"])
        ->get();

    $deletedCount = 0;
    $totalQuestions = 0;
    $totalCertificates = 0;
    $totalVersions = 0;
    $totalStatistics = 0;
    $totalTemplates = 0;

    foreach ($certifications as $certification) {
        foreach ($certification->certificates as $certificate) {
            $storageService->delete($certificate->certificate_image_path);
        }

        $questionIds = $certification->questions->pluck("id")->all();
        $certificateIds = $certification->certificates->pluck("id")->all();
        $versionIds = $certification->versions->pluck("id")->all();
        $statisticsIds = $certification->statistics->pluck("id")->all();
        $templateId = $certification->certificateTemplate?->id;

        $totalQuestions += count($questionIds);
        $totalCertificates += count($certificateIds);
        $totalVersions += count($versionIds);
        $totalStatistics += count($statisticsIds);
        if ($templateId !== null) {
            $totalTemplates++;
        }

        AuditLog::query()
            ->where(function ($query) use ($certification, $questionIds, $certificateIds, $versionIds, $statisticsIds, $templateId): void {
                $query->where(function ($subQuery) use ($certification): void {
                    $subQuery->where("entity", "Certification")
                        ->where("entity_id", $certification->id);
                })->orWhere(function ($subQuery) use ($questionIds): void {
                    $subQuery->where("entity", "Question")
                        ->whereIn("entity_id", $questionIds);
                })->orWhere(function ($subQuery) use ($certificateIds): void {
                    $subQuery->where("entity", "Certificate")
                        ->whereIn("entity_id", $certificateIds);
                })->orWhere(function ($subQuery) use ($versionIds): void {
                    $subQuery->where("entity", "CertificationVersion")
                        ->whereIn("entity_id", $versionIds);
                })->orWhere(function ($subQuery) use ($statisticsIds): void {
                    $subQuery->where("entity", "CertificationStatistic")
                        ->whereIn("entity_id", $statisticsIds);
                })->orWhere(function ($subQuery) use ($templateId): void {
                    if ($templateId === null) {
                        $subQuery->whereRaw("1 = 0");
                        return;
                    }

                    $subQuery->where("entity", "CertificateTemplate")
                        ->where("entity_id", $templateId);
                });
            })
            ->delete();

        $certification->delete();
        $deletedCount++;
    }

    echo "DELETED_CERTIFICATIONS:$deletedCount\n";
    echo "DELETED_QUESTIONS:$totalQuestions\n";
    echo "DELETED_CERTIFICATES:$totalCertificates\n";
    echo "DELETED_VERSIONS:$totalVersions\n";
    echo "DELETED_STATISTICS:$totalStatistics\n";
    echo "DELETED_TEMPLATES:$totalTemplates\n";
    ' >/tmp/delete_all_cert_result.txt 2>&1

    print_success "Todas las certificaciones fueron eliminadas"
    cat /tmp/delete_all_cert_result.txt
}

main() {
    clear
    print_header "ELIMINAR CERTIFICACIONES"

    print_warning "Este script elimina datos de forma irreversible."
    print_info "Se borran certificaciones y registros relacionados; las imágenes asociadas también se limpian."
    echo ""

    check_project_root
    check_laravel_env
    check_database_connection

    input_mode
    input_target
    confirm_deletion

    if [ "$MODE" = "single" ]; then
        delete_single
    else
        delete_all
    fi

    echo ""
    print_success "Proceso completado"
}

main
