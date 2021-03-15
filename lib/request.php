<?php
    $fromUrl = false;
    if(isset($_REQUEST['endpoint']) || isset($_REQUEST['json_data'])):
        $className = !empty($_REQUEST['classname']) ? $_REQUEST['classname'] : 'Foo';
        
        if(isset($_REQUEST['json_data'])) {
            $jsonResult = $_REQUEST['json_data'];
        } else {
            $fromUrl = true;

            $url = $_REQUEST['endpoint'] ?? '';
    
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $result = curl_exec($ch);
            
            $reqInfo = curl_getinfo($ch);
    
            $content_type = $reqInfo['content_type'];
            $http_code = $reqInfo['http_code'];
            $total_time = $reqInfo['total_time'];
            $primary_ip = $reqInfo['primary_ip'];
            $primary_port = $reqInfo['primary_port'];
    
            curl_close($ch);
    
            $jsonResult = prettyPrint($result);

        }

        $jsonObject = json_decode($jsonResult);

        $typescript_code = "\n\n== Interface ==\n\n";
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

                    $key$optional: $type,
                EOD;

            endforeach;

        endif;

        $interface .= "\n}";
        return $interface;

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
            
                endpoint = (Endpoint.baseUrl)+"/$endpoint".;

                constructor(protected http: HttpClient) {
                    super(http);
                }
            }
            EOD;

        return $service;
    }

    function getEditTypescriptCode($className) {

        $endpoint = strtolower(singular_noun($className));

        $editTs = <<< EOD
        import { Location } from '@angular/common';
        import { Component, OnInit, Input } from '@angular/core';
        import { ActivatedRoute } from '@angular/router';
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
            private location: Location
            ) { }
        
          ngOnInit(): void {
            let id = this.route.snapshot.params.id
            this.{$className}Service.getOne<$className>(id).subscribe((data: $className)=> {
              this.$endpoint = data;
            })
          }
        
          applyUpdateFoo() {
            this.{$endpoint}Service.update<$className>(this.$endpoint).subscribe((data)=>{
        
              toastAlert().fire({
                title: "Terminé!",
                text: "La donnéé a été sauvegardée",
                icon: "success",
              })
              
            });
          }
        
        }
        
        EOD;

        return $editTs;
    }

    function getCreateTypescriptCode($json, $className) {

        $endpoint = strtolower(singular_noun($className));

        $attributes = '';
        $assignements = '';

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
                endswitch;

                if($type != "any") {
                    
                    $attributes .= <<< EOD
    
                        $key: $type;
                    EOD;
    
                    $assignements .= <<< EOD
                            
                            $key: this.$type,
                    EOD;

                }

            endforeach;

        endif;

        $createTs = <<< EOD
        import { Component, Input, OnInit } from '@angular/core';
        import { toastAlert } from 'src/app/lib/swalAlert';
        
        @Component({
          selector: 'app-$endpoint-create-form',
          templateUrl: './$endpoint-create-form.component.html',
          styleUrls: ['./$endpoint-create-form.component.css']
        })
        export class {$className}CreateFormComponent implements OnInit {
          $attributes
        
          constructor(private {$endpoint}Service: {$className}Service) { }
        
          ngOnInit(): void {
          }
        
          saveNew{$className}() {
        
            let new{$className} = {
              $assignements

            } as Foo;
        
            this.{$endpoint}Service.create(new{$className}).subscribe((data:any) => {
              
              toastAlert().fire({
                title: "Terminé!",
                text: "La donnée a été sauvegardée",
                icon: "success",
              })
        
            });
          }
        
        }
        
        EOD;

        return $createTs;
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
            <div>
                
                <div class="form-row">
                    $inputs

                </div>
                
                <button type="submit" class="btn btn-primary" (click)="saveNew{$className}()">Mettre à jour</button>
                
            </div>

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
        selector: 'app-$endpoint',
        templateUrl: './$endpoint.component.html',
        styleUrls: ['./$endpoint.component.css']
        })
        export class {$className}Component implements OnInit {

            $endpoint!: {$className}[]; 

            constructor() { }

            ngOnInit(): void {
                this.getList{$endpoint_plurial_ucword}();
            }

            getList{$endpoint_plurial_ucword}() {
                this.{$endpoint_singular}Service.getAll<$className>().subscribe(
                ($endpoint: any) => {
                    this.$endpoint = $endpoint;
                }
                );
            }

            onDelectAction($endpoint_singular: $endpoint_plurial_ucword) {
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
                    this.{$endpoint_singular}Service.delete({$endpoint_singular}.id).subscribe((data)=>{
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

        return $list;
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
                <div class="table-responsiv">
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
                                        <button routerLink="/product-categories/edit/{{$endpoint_singular}.id}"
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
                        `Backend returned code ${error.status}, ` +
                        `body was: ${error.error}`);

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
