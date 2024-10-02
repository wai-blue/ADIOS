import React, { Component } from 'react';
import axios, { AxiosError, AxiosResponse } from "axios";

import Swal from "sweetalert2";

interface ApiResponse<T> {
  data: T;
}

interface ApiError {
  message: string;
}

class Request {

  getAppUrl(): string {
    return globalThis.app.config.url + '/';
  }

  public get<T>(
    url: string,
    queryParams: Record<string, any>,
    successCallback?: (data: ApiResponse<T>) => void,
    errorCallback?: (data: any) => void,
  ): void {
    document.body.classList.add("ajax-loading");
    axios.get<T, AxiosResponse<ApiResponse<T>>>(this.getAppUrl() + url, {
      params: queryParams
    }).then(res => {
      const responseData: ApiResponse<T> = res.data;
      document.body.classList.remove("ajax-loading");
      if (successCallback) successCallback(responseData);
    }).catch((err: AxiosError<ApiError>) => this.catchHandler(url, err, errorCallback));
  }

  public post<T>(
    url: string,
    postData: Record<string, any>,
    queryParams?: Record<string, string>|{},
    successCallback?: (data: ApiResponse<T>) => void,
    errorCallback?: (data: any) => void,
  ): void {
    document.body.classList.add("ajax-loading");
    axios.post<T, AxiosResponse<ApiResponse<T>>>(this.getAppUrl() + url, postData, {
      params: queryParams
    }).then(res => {
      const responseData: ApiResponse<T> = res.data;
      document.body.classList.remove("ajax-loading");
      if (successCallback) successCallback(responseData);
    }).catch((err: AxiosError<ApiError>) => this.catchHandler(url, err, errorCallback));
  }

  public put<T>(
    url: string,
    putData: Record<string, any>,
    queryParams?: Record<string, string>|{},
    successCallback?: (data: ApiResponse<T>) => void,
    errorCallback?: (data: any) => void,
  ): void {
    axios.put<T, AxiosResponse<ApiResponse<T>>>(this.getAppUrl() + url, putData, {
      params: queryParams
    }).then(res => {
      const responseData: ApiResponse<T> = res.data;
      if (successCallback) successCallback(responseData);
    }).catch((err: AxiosError<ApiError>) => this.catchHandler(url, err, errorCallback));
  }

  public patch<T>(
    url: string,
    patchData: Record<string, any>,
    queryParams?: Record<string, string>|{},
    successCallback?: (data: ApiResponse<T>) => void,
    errorCallback?: (data: any) => void,
  ): void {
    axios.patch<T, AxiosResponse<ApiResponse<T>>>(this.getAppUrl() + url, patchData, {
      params: queryParams
    }).then(res => {
      const responseData: ApiResponse<T> = res.data;
      if (successCallback) successCallback(responseData);
    }).catch((err: AxiosError<ApiError>) => this.catchHandler(url, err, errorCallback));
  }

  public delete<T>(
    url: string,
    queryParams: Record<string, any>,
    successCallback?: (data: ApiResponse<T>) => void,
    errorCallback?: (data: any) => void,
  ): void {
    axios.delete<T, AxiosResponse<ApiResponse<T>>>(this.getAppUrl() + url, {
      params: queryParams
    }).then(res => {
      const responseData: ApiResponse<T> = res.data;
      if (successCallback) successCallback(responseData);
    }).catch((err: AxiosError<ApiError>) => this.catchHandler(url, err, errorCallback));
  }

  private catchHandler(
    url: string,
    err: AxiosError<ApiError>,
    errorCallback?: (data: any) => void
  ) {
    if (err.response) {
      if (err.response.status == 500) {
        this.fatalErrorNotification(err.response.data.message);
      } else {
        this.fatalErrorNotification(err.response.data.message ?? 'Unknown error.');
        console.error('ADIOS: ' + err.code, err.config?.url, err.config?.params, err.response.data);
        if (errorCallback) errorCallback(err.response);
      }
    } else {
      console.error('ADIOS: Request @ ' + url + ' unknown error.');
      console.error(err);
      this.fatalErrorNotification("Unknown error");
    }
  }

  private fatalErrorNotification(error: any) {
    if (typeof error == 'string') {
      try {
        error = JSON.parse(error);
      } catch (ex) {
        //
      }
    }

    globalThis.app.showDialogDanger(globalThis.app.makeErrorResultReadable(error));
  }

}

const request = new Request();
export default request;
