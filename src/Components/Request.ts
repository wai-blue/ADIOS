import axios, { AxiosError, AxiosResponse } from "axios";
import Notification from "./Notification";

import Swal from "sweetalert2";

interface ApiResponse<T> {
  data: T;
}

interface ApiError {
  message: string;
}

class Request {

  appUrl: string = globalThis._APP_URL + '/';

  public get<T>(
    url: string,
    queryParams: Record<string, any>,
    successCallback?: (data: ApiResponse<T>) => void,
    errorCallback?: (data: any) => void,
  ): void {
    axios.get<T, AxiosResponse<ApiResponse<T>>>(this.appUrl + url, {
      params: queryParams
    }).then(res => {
      const responseData: ApiResponse<T> = res.data;
      if (successCallback) successCallback(responseData);
    }).catch((err: AxiosError<ApiError>) => this.catchHandler(err, errorCallback));
  }

  public post<T>(
    url: string,
    postData: Record<string, any>,
    queryParams?: Record<string, string>|{},
    successCallback?: (data: ApiResponse<T>) => void,
    errorCallback?: (data: any) => void,
  ): void {
    axios.post<T, AxiosResponse<ApiResponse<T>>>(this.appUrl + url, postData, {
      params: queryParams
    }).then(res => {
      const responseData: ApiResponse<T> = res.data;
      if (successCallback) successCallback(responseData);
    }).catch((err: AxiosError<ApiError>) => this.catchHandler(err, errorCallback));
  }

  public put<T>(
    url: string,
    putData: Record<string, any>,
    queryParams?: Record<string, string>|{},
    successCallback?: (data: ApiResponse<T>) => void,
    errorCallback?: (data: any) => void,
  ): void {
    axios.put<T, AxiosResponse<ApiResponse<T>>>(this.appUrl + url, putData, {
      params: queryParams
    }).then(res => {
      const responseData: ApiResponse<T> = res.data;
      if (successCallback) successCallback(responseData);
    }).catch((err: AxiosError<ApiError>) => this.catchHandler(err, errorCallback));
  }

  public patch<T>(
    url: string,
    patchData: Record<string, any>,
    queryParams?: Record<string, string>|{},
    successCallback?: (data: ApiResponse<T>) => void,
    errorCallback?: (data: any) => void,
  ): void {
    axios.patch<T, AxiosResponse<ApiResponse<T>>>(this.appUrl + url, patchData, {
      params: queryParams
    }).then(res => {
      const responseData: ApiResponse<T> = res.data;
      if (successCallback) successCallback(responseData);
    }).catch((err: AxiosError<ApiError>) => this.catchHandler(err, errorCallback));
  }

  public delete<T>(
    url: string,
    queryParams: Record<string, any>,
    successCallback?: (data: ApiResponse<T>) => void,
    errorCallback?: (data: any) => void,
  ): void {
    axios.delete<T, AxiosResponse<ApiResponse<T>>>(this.appUrl + url, {
      params: queryParams
    }).then(res => {
      const responseData: ApiResponse<T> = res.data;
      if (successCallback) successCallback(responseData);
    }).catch((err: AxiosError<ApiError>) => this.catchHandler(err, errorCallback));
  }

  private catchHandler(
    err: AxiosError<ApiError>,
    errorCallback?: (data: any) => void
  ) {
    if (err.response) {
      if (err.response.status == 500) {
        this.fatalErrorNotification(err.response.data.message);
      } else {
        Notification.error(err.response.data);
        if (errorCallback) errorCallback(err.response);
      }
    } else {
      this.fatalErrorNotification("Unknown error");
    }
  }

  private fatalErrorNotification(errorText: string) {
    Swal.fire({
      text: errorText,
      width: 600,
      padding: "3em",
      color: "#ad372a",
      background: "white",
      backdrop: `rgba(123,12,0,0.2)`
    });
  }

}

const request = new Request();
export default request;
