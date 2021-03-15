<?php
    require_once('./getPlurial.php');
    require_once('./getSingular.php');
    require_once('./prettier.php');

    $fromUrl = false;
    $reqInfo = array();
    if(isset($_POST['endpoint']) || isset($_POST['json_data'])):
        $className = !empty($_REQUEST['classname']) ? $_REQUEST['classname'] : 'Foo';

        if(isset($_REQUEST['json_data'])) {

            $jsonResult = prettyPrint($_REQUEST['json_data']);
        } else {

            $fromUrl = true;

            $url = $_REQUEST['endpoint'] ?? '';

            $result = file_get_contents($url);
    
            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, $url);
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            // $result = curl_exec($ch);
            
            // $reqInfo = curl_getinfo($ch);
    
            // $content_type = $reqInfo['content_type'];
            // $http_code = $reqInfo['http_code'];
            // $total_time = $reqInfo['total_time'];
            // $primary_ip = $reqInfo['primary_ip'];
            // $primary_port = $reqInfo['primary_port'];
    
            curl_close($ch);
    
            $jsonResult = prettyPrint($result);

        }

        $jsonObject = json_decode($jsonResult);

        if(empty($jsonObject)) {
            header("HTTP/1.0 422 Unprocessable Entity | Data received not well formated in JSON");
            // http_response_code(400);
            exit("Can't process received data");
        }

        $typescript_code = "\n\n== Command Lines ==\n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getCommandLineNg($className).'</pre>';
        $typescript_code .= "\n\n== RouteLinks ==\n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getRouteLinks($className).'</pre>';
        $typescript_code .= "\n\n== Interface ==\n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getInterfaceTypescriptCode($jsonObject, $className).'</pre>';
        $typescript_code .= "\n\n== Service == \n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getServiceTypescriptCode($className).'</pre>';
        $typescript_code .= "\n\n== Create == \n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getCreateTypescriptCode($jsonObject, $className).'</pre>';
        $typescript_code .= "\n\n== Create Form == \n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getCreateFormTypescriptCode($jsonObject, $className).'</pre>';
        $typescript_code .= "\n\n== Edit == \n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getEditTypescriptCode($className).'</pre>';
        $typescript_code .= "\n\n== Edit Form == \n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getEditFormTypescriptCode($jsonObject, $className).'</pre>';
        $typescript_code .= "\n\n== List == \n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getListTypescriptCode($jsonObject, $className).'</pre>';
        $typescript_code .= "\n\n== List HTML == \n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getListHtmlTypescriptCode($jsonObject, $className).'</pre>';
        $typescript_code .= "\n\n== Base Service == \n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getBaseServiceTypescriptCode($jsonObject, $className).'</pre>';
        $typescript_code .= "\n\n== toastAlert Function == \n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getSweetAlertCustomToast($jsonObject, $className).'</pre>';
        $typescript_code .= "\n\n== Endpoint Function == \n\n";
        $typescript_code .= '<pre style="background-color: #333; color: white; font-weight: 100 !important;">'.getEndpointInterface().'</pre>';


        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode([
            "typescript_code" => $typescript_code,
            "content_type" => $reqInfo['content_type'] ?? null,
            "http_code" => $reqInfo['http_code'] ?? null,
            "total_time" => $reqInfo['total_time'] ?? null,
            "primary_ip" => $reqInfo['primary_ip'] ?? null,
            "primary_port" => $reqInfo['primary_port'] ?? null,
            "url" => $_REQUEST['endpoint'] ?? null,
            "json" => $jsonResult,
        ]);
    else:
        http_response_code(400);
    endif;

    function getInterfaceTypescriptCode($json, $className) {

        $interface= "export interface $className {";
        if(is_array($json)):
            $json = $json[0];
        endif;
        
        if(is_object($json)):

            foreach($json as $key => $value):
                switch(gettype($value)):
                    case "string":
                        $type = "string";
                        break;
                    case "integer":
                    case "double":
                    case "float":
                        $type = "number";
                        break;
                    default:
                        $type = "any";
                        break;
                endswitch;

                $optional = $type == 'any' ? '?':'';
                $interface .= <<< EOD

                    $key$optional: $type;
                EOD;

            endforeach;

        endif;

        $interface .= "\n}";
        return htmlspecialchars($interface);

    }

    function getServiceTypescriptCode($className) {

        $endpoint = strtolower(plurial_noun($className));

        $service = <<< EOD
        import { Injectable } from '@angular/core';
        EOD;
        
        $service .= <<< EOD

        @Injectable({
                providedIn: 'root'
        })
        export class {$className}Service extends BaseService {
        EOD;
            
            $service .= <<< EOD
            
                endpoint = (Endpoint.baseUrl)+"/$endpoint";

                constructor(protected http: HttpClient) {
                    super(http);
                }
            }
            EOD;

        return htmlspecialchars($service);
    }

    function getEditTypescriptCode($className) {

        $endpoint = strtolower(singular_noun($className));
        $endpoint_plurial = strtolower(plurial_noun($className));

        $editTs = <<< EOD
        import { Location } from '@angular/common';
        import { Component, OnInit, Input } from '@angular/core';
        import { ActivatedRoute, Router } from '@angular/router';
        import { toastAlert } from 'src/app/lib/swalAlert';
        
        @Component({
          selector: 'app-$endpoint-edit-form',
          templateUrl: './$endpoint-edit-form.component.html',
          styleUrls: ['./$endpoint-edit-form.component.css']
        })
        export class {$className}EditFormComponent implements OnInit {
        
          $endpoint!: $className;
        
          constructor(
            private {$endpoint}Service: {$className}Service,
            private route: ActivatedRoute,
            private router: Router,
            private location: Location
            ) { }
        
          ngOnInit(): void {
            let id = this.route.snapshot.params.id
            this.{$endpoint}Service.getOne<{$className}>(id).subscribe((data: $className)=> {
              this.$endpoint = data;
            })
          }
        
          applyUpdate{$className}() {
            this.{$endpoint}Service.update<$className>(this.$endpoint).subscribe((data: any)=>{
        
              toastAlert().fire({
                title: "Terminé!",
                text: "La donnéé a été sauvegardée",
                icon: "success",
              });
              
              this.router.navigateByUrl("/$endpoint_plurial");
              
            });
          }
        
        }
        
        EOD;

        return htmlspecialchars($editTs);
    }

    function getCreateTypescriptCode($json, $className) {

        $endpoint = strtolower(singular_noun($className));
        $endpoint_plurial = strtolower(plurial_noun($className));

        $attributes = '';
        $assignements = '';

        if(is_array($json)):
            $json = $json[0];
        endif;
        
        if(is_object($json)):

            foreach($json as $key => $value):
                switch(gettype($value)):
                    case "string":
                    case "NULL":
                        $type = "string";
                        break;
                    case "integer":
                    case "double":
                    case "float":
                        $type = "number";
                        break;
                    default:
                        $type = "any";
                endswitch;

                if($type != "any") {
                    
                    $attributes .= <<< EOD
    
                        $key!: $type;
                    EOD;
    
                    $assignements .= <<< EOD
                            
                            $key: this.$key,
                    EOD;

                }

            endforeach;

        endif;

        $createTs = <<< EOD
        import { Component, Input, OnInit } from '@angular/core';
        import { toastAlert } from 'src/app/lib/swalAlert';
        import { Router } from '@angular/router';
        
        @Component({
          selector: 'app-$endpoint-create-form',
          templateUrl: './$endpoint-create-form.component.html',
          styleUrls: ['./$endpoint-create-form.component.css']
        })
        export class {$className}CreateFormComponent implements OnInit {
          $attributes
        
          constructor(private {$endpoint}Service: {$className}Service, private router: Router) { }
        
          ngOnInit(): void {
          }
        
          saveNew{$className}() {
        
            let new{$className} = {
              $assignements

            } as $className;
        
            this.{$endpoint}Service.create(new{$className}).subscribe((data:any) => {
              
              toastAlert().fire({
                title: "Terminé!",
                text: "La donnée a été sauvegardée",
                icon: "success",
              });
              
              this.router.navigateByUrl("/{$endpoint_plurial}");
        
            });
          }
        
        }
        
        EOD;

        return htmlspecialchars($createTs);
    }

    function getEditFormTypescriptCode($json, $className) {

        $endpoint = strtolower(singular_noun($className));

        $inputs = '';

        if(is_array($json)):
            $json = $json[0];
        endif;
        
        if(is_object($json)):


            foreach($json as $key => $value):
                switch(gettype($value)):
                    case "string":
                    case "NULL":
                        $type = "text";
                        break;
                    case "integer":
                    case "double":
                    case "float":
                        $type = "number";
                        break;
                    default:
                        $type = "any";
                endswitch;

                if($type != "any") {
                    
                    $inputs .= <<< EOD
                    
                            <div class="form-group col-md-4">
                                <label for="">$key</label>
                                <input [(ngModel)]="$endpoint.$key" type="$type" class="form-control" placeholder="$key">
                            </div>
                    EOD;

                }

            endforeach;

        endif;

        $editForm = <<< EOD
            <div *ngIf="$endpoint">
                
                <div class="form-row">
                    $inputs

                </div>
                
                <button type="submit" class="btn btn-primary" (click)="applyUpdate{$className}()">Mettre à jour</button>
                
            </div>

            EOD;
            
        return htmlspecialchars($editForm);
    }
    
    function getCreateFormTypescriptCode($json, $className) {

        $inputs = '';

        if(is_array($json)):
            $json = $json[0];
        endif;
        
        if(is_object($json)):


            foreach($json as $key => $value):
                switch(gettype($value)):
                    case "string":
                    case "NULL":
                        $type = "text";
                        break;
                    case "integer":
                    case "double":
                    case "float":
                        $type = "number";
                        break;
                    default:
                        $type = "any";
                endswitch;

                if($type != "any") {
                    
                    $inputs .= <<< EOD
                    
                            <div class="form-group col-md-4">
                                <label for="">$key</label>
                                <input [(ngModel)]="$key" type="$type" class="form-control" placeholder="$key">
                            </div>
                    EOD;

                }

            endforeach;

        endif;

        $createForm = <<< EOD
                <h2>Nouveau</h2>
                <div class="form-row">
                    $inputs
        
                </div>
                
                <button type="submit" class="btn btn-primary" (click)="saveNew{$className}()">Sauvegarder</button>
                

            EOD;
        return htmlspecialchars($createForm);
    }

    function getListTypescriptCode($jsonObject, $className) {

        $endpoint = strtolower(plurial_noun($className));
        $endpoint_singular = strtolower(singular_noun($className));
        $endpoint_plurial_ucword = ucfirst(plurial_noun($className));

        $list = <<< EOD
        import { Component, OnInit } from '@angular/core';
        import { toastAlert } from 'src/app/lib/swalAlert';
        import Swal from 'sweetalert2';

        @Component({
        selector: 'list-app-$endpoint',
        templateUrl: './list-$endpoint_singular.component.html',
        styleUrls: ['./list-$endpoint_singular.component.css']
        })
        export class List{$className}Component implements OnInit {

            $endpoint!: {$className}[]; 

            constructor(private {$endpoint_singular}Service: {$className}Service) { }

            ngOnInit(): void {
                this.getList{$endpoint_plurial_ucword}();
            }

            getList{$endpoint_plurial_ucword}() {
                this.{$endpoint_singular}Service.getAll<{$className}>().subscribe(
                ($endpoint: {$className}[]) => {
                    this.$endpoint = $endpoint;
                }
                );
            }

            onDelectAction($endpoint_singular: $className) {
                toastAlert().fire({
                icon: 'success',
                title: 'Signed in successfully'
                })

                Swal.queue([{
                focusCancel: true,
                title: "Etes-vous sûr?",
                text:"Notez que cette action est irrévocable! La suppression de ce produit supprimera aussi toute activité relatif.",
                showLoaderOnConfirm: true,
                icon: "question",
                showCancelButton: true,
                cancelButtonText: "Annuler",
                showConfirmButton: true,
                confirmButtonText: 'Supprimer',
                confirmButtonColor: "orange",
                }])
                .then((userAnswer: any) => {

                if (userAnswer.value[0] == true) {
                    this.{$endpoint_singular}Service.delete({$endpoint_singular}.id).subscribe((data: any)=>{
                    // remove deleted object from the dataset
                    this.$endpoint.splice(this.$endpoint.indexOf({$endpoint_singular}), 1);
                    
                    toastAlert().fire({
                        title: "La donnée a été supprimée!",
                        icon: "success",
                    });
                    });
                } else {

                    toastAlert().fire({
                    title: "Vous avez annulé l'opération",
                    icon: "success",
                    });
                    
                }
                });
            }

        }
        EOD;

        return htmlspecialchars($list);
    }

    function getListHtmlTypescriptCode($jsonObject, $className) {

        $endpoint = strtolower(plurial_noun($className));
        $endpoint_singular = strtolower(singular_noun($className));
        $endpoint_plurial_ucword = ucfirst(plurial_noun($className));


        $ths = '';
        $tds = '';

        if(is_array($jsonObject)):
            $jsonObject = $jsonObject[0];
        endif;
        
        if(is_object($jsonObject)):

            foreach($jsonObject as $key => $value):
                switch(gettype($value)):
                    case "string":
                        $type = "string";
                        break;
                    case "integer":
                    case "double":
                    case "float":
                        $type = "number";
                        break;
                    default:
                        $type = "any";
                endswitch;

                if($type != "any") {
                    
                    $ths .= <<< EOD
                        
                                            <th> $key </th>
                    EOD;
    
                    $tds .= <<< EOD
                        
                                            <td>{{ $endpoint_singular.$key }}</td>
                    EOD;

                }

            endforeach;

        endif;


        $html = <<< EOD
        <div class="jumbotro">
            <button type="button" class="btn btn-info" routerLink="/$endpoint/new">Nouveau $endpoint_singular</button>
        </div>
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    Liste des $endpoint
                </h4>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead class=" text-primary">
                            <tr>
                                $ths
                                <th class="text-right"> Action </th>

                            </tr>
                        </thead>
                        <tbody>
                            <tr *ngFor="let $endpoint_singular of $endpoint">
                                $tds
                                <td class="text-right">
                                    <!-- Single button -->
                                    <div class="btn-group  btn-group-xs">
                                        <button routerLink="/$endpoint/edit/{{{$endpoint_singular}.id}}"
                                            class="btn button-small btn-primary btn-fab btn-icon">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button (click)="onDelectAction($endpoint_singular)"
                                            class="btn btn-danger btn-fab btn-icon">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                    </table>
                </div>
            </div>
        </div>
        EOD;

        return htmlspecialchars($html);
    }

    function getBaseServiceTypescriptCode() {
        return <<< EOD
        import { HttpClient, HttpErrorResponse } from "@angular/common/http";
        import { Observable, throwError, of } from "rxjs";
        import { catchError, retry } from "rxjs/operators";
        import { toastAlert } from "../lib/swalAlert";

        export class BaseService {

            endpoint!: string;

            constructor(protected http: HttpClient) { }

            getAll<T>(): Observable<T[]> {
                return this.http.get<T[]>(this.endpoint);
            }

            getOne<T>(id: any): Observable<T> {
                return this.http.get<T>(this.endpoint+"/"+id);
            }

            create<T>(newObject: T) {
                return this.http.post<T>(this.endpoint, newObject)
                .pipe(
                catchError(this.handleError)
                );
            }

            update<T extends {id: any}>(object: T) {
                return this.http.put<T>(this.endpoint+"/"+object.id, object)
                .pipe(
                catchError(this.handleError)
                );
            }

            delete<T>(id: number) {
                return this.http.delete<T>(this.endpoint+"/"+id)
                .pipe(
                catchError(this.handleError)
                );
            }

            private handleError(error: HttpErrorResponse) {
                if (error.error instanceof ErrorEvent) {
                // A client-side or network error occurred. Handle it accordingly.
                console.error('An error occurred:', error.error.message);
                    toastAlert().fire({
                        title: ""+error.status+"",
                        text: error.error.message,
                        icon: "error",
                    });
                } else {

                    console.error(
                        "Backend returned code "+error.status+", " +
                        "body was: "+error.error);

                    toastAlert().fire({
                        title: ""+error.status+"",
                        text: error.error.message,
                        icon: "error",
                    });
                }

                return throwError('Something bad happened; please try again later.');
            }
        }
        EOD;
    }

    function getSweetAlertCustomToast() {
        return <<< EOD
        import Swal from "sweetalert2"

        export function toastAlert() : typeof Swal {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })

            return Toast;
        }
        EOD;
    }
    
    function getCommandLineNg($className) {
            $endpoint = strtolower(plurial_noun($className));
            $endpoint_singular = strtolower(singular_noun($className));
            $endpoint_plurial_ucword = ucfirst(plurial_noun($className));
                
            return <<< EOD
            ng generate service services/$endpoint_singular/$endpoint_singular
            ng generate component components/$endpoint/$endpoint_singular-create-form
            ng generate component components/$endpoint/$endpoint_singular-edit-form
            ng generate component components/$endpoint/list-$endpoint_singular
            ng generate component components/$endpoint/view-$endpoint_singular
            echo. > src/app/interfaces/$endpoint_singular.ts
            
            
            EOD;
    }
    
    function getRouteLinks($className) {
            $endpoint = strtolower(plurial_noun($className));
            $endpoint_singular = strtolower(singular_noun($className));
            $endpoint_plurial_ucword = ucfirst(plurial_noun($className));
            
           return <<< EOD
           
           // $endpoint
                   {path: '$endpoint', component: List{$className}Component},
                   {path: '$endpoint/edit/:id', component: {$className}EditFormComponent},
                   {path: '$endpoint/new', component: {$className}CreateFormComponent},
             
           EOD;
    }
    
    
    function getEndpointInterface() {
            return <<< EOD
                export class Endpoint {
                    static baseUrl: string = "http://192.168.1.15:88/api";
                
                    public static getBaseUrl() {
                        return Endpoint.baseUrl;
                    }
                }
            EOD;
    }
